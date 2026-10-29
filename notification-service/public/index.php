<?php

// Controlled receiver only. Never sends email/SMS and never serves private files.
header('Content-Type: application/json');
function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}
set_exception_handler(function (Throwable $error): void {
    respond(503, ['code' => 'receiver_storage_unavailable']);
});
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    respond(403, ['code' => 'loopback_only']);
}
if ($path === '/health' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    respond(200, ['status' => 'ok', 'service' => 'controlled-notification-receiver']);
}
if (! is_file(dirname(__DIR__).'/.env')) {
    respond(503, ['code' => 'receiver_not_configured']);
}
$settings = parse_ini_file(dirname(__DIR__).'/.env', false, INI_SCANNER_RAW);
$key = $settings['SERVICE_KEY'] ?? '';
if (strlen($key) < 32 || ! hash_equals('Bearer '.$key, $_SERVER['HTTP_AUTHORIZATION'] ?? '')) {
    respond(401, ['code' => 'unauthorized']);
}
$pdo = new PDO('sqlite:'.dirname(__DIR__).'/storage/receiver.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA busy_timeout=2000');
$pdo->exec('CREATE TABLE IF NOT EXISTS receipts (work_key TEXT PRIMARY KEY, payload_hash TEXT NOT NULL, receipt TEXT NOT NULL UNIQUE, accepted_at TEXT NOT NULL)');
$pdo->exec('CREATE TABLE IF NOT EXISTS controls (work_key TEXT PRIMARY KEY, mode TEXT NOT NULL, calls INTEGER NOT NULL DEFAULT 0)');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! in_array($path, ['/notify', '/test/control'], true)) {
    respond(404, ['code' => 'not_found']);
}
$raw = file_get_contents('php://input', false, null, 0, 16385);
if (strlen($raw) > 16384 || ! is_array($body = json_decode($raw, true)) || ! is_string($body['work_key'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/', $body['work_key'])) {
    respond(422, ['code' => 'invalid_request']);
}
$workKey = $body['work_key'];
if ($path === '/test/control') {
    if (! in_array($body['mode'] ?? '', ['success', 'transient_once', 'permanent', 'unavailable', 'ack_loss_once', 'timeout_once'], true)) {
        respond(422, ['code' => 'invalid_mode']);
    }
    $query = $pdo->prepare('INSERT INTO controls(work_key,mode,calls) VALUES(?,?,0) ON CONFLICT(work_key) DO UPDATE SET mode=excluded.mode,calls=0');
    $query->execute([$workKey, $body['mode']]);
    respond(200, ['configured' => true]);
}
if (array_diff(array_keys($body), ['work_key', 'correlation_id', 'kind', 'recipient_id', 'scheduled_for', 'data'])
    || ! in_array($body['kind'] ?? '', ['reminder', 'missed_session', 'action_deadline', 'progress_summary'], true)
    || ! is_int($body['recipient_id'] ?? null) || $body['recipient_id'] < 1 || ! is_array($body['data'] ?? null)) {
    respond(422, ['code' => 'invalid_request']);
}
$allowedData = match ($body['kind']) {
    'reminder' => ['consultation_id', 'schedule_version'],
    'missed_session' => ['consultation_id', 'requires_human_review', 'basis', 'transition_correlation_id'],
    'action_deadline' => ['action_item_id', 'deadline_version'],
    'progress_summary' => ['adviser_id', 'local_date', 'counts'],
};
if (array_diff(array_keys($body['data']), $allowedData)) {
    respond(422, ['code' => 'nonminimal_payload']);
}
// Serialize receipt lookup/acceptance and fault counters across service processes.
$pdo->exec('BEGIN IMMEDIATE');
$hash = hash('sha256', json_encode($body));
$query = $pdo->prepare('SELECT * FROM receipts WHERE work_key=?');
$query->execute([$workKey]);
if ($receipt = $query->fetch(PDO::FETCH_ASSOC)) {
    $pdo->exec('COMMIT');
    if (! hash_equals($receipt['payload_hash'], $hash)) {
        respond(409, ['code' => 'identity_payload_conflict']);
    }
    respond(200, ['work_key' => $workKey, 'receipt' => $receipt['receipt'], 'duplicate' => true]);
}
$query = $pdo->prepare('SELECT mode,calls FROM controls WHERE work_key=?');
$query->execute([$workKey]);
$control = $query->fetch(PDO::FETCH_ASSOC) ?: ['mode' => 'success', 'calls' => 0];
$query = $pdo->prepare('UPDATE controls SET calls=calls+1 WHERE work_key=?');
$query->execute([$workKey]);
$mode = $control['mode'];
if ($mode === 'permanent' || $mode === 'unavailable' || ($mode === 'transient_once' && $control['calls'] === 0)) {
    $pdo->exec('COMMIT');
    respond($mode === 'permanent' ? 422 : 503, ['code' => 'controlled_failure']);
}
$receipt = 'rcpt_'.bin2hex(random_bytes(16));
$query = $pdo->prepare('INSERT INTO receipts(work_key,payload_hash,receipt,accepted_at) VALUES(?,?,?,?)');
$query->execute([$workKey, $hash, $receipt, gmdate('c')]);
$pdo->exec('COMMIT');
if ($mode === 'timeout_once' && $control['calls'] === 0) {
    sleep(6); // Exceeds the worker's five-second HTTP timeout; still bounded.
}
if ($mode === 'ack_loss_once' && $control['calls'] === 0) {
    respond(503, ['code' => 'controlled_lost_ack_after_acceptance']);
}
respond(201, ['work_key' => $workKey, 'receipt' => $receipt, 'duplicate' => false]);
