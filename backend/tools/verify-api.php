<?php

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

// Controlled HTTP evidence only. Cookies/passwords stay in memory and are never output.
$settings = Dotenv::parse(file_get_contents(__DIR__.'/../.env'));
if (($settings['APP_ENV'] ?? '') !== 'local' || ($settings['DB_DATABASE'] ?? '') !== 'thesis_consultation_week13') {
    throw new RuntimeException('This script is limited to the local Project 29 demonstration.');
}
$base = 'http://127.0.0.1:8000';
$records = [];
$run = bin2hex(random_bytes(6));
$handles = [];

function client(): CurlHandle
{
    $client = curl_init();
    curl_setopt_array($client, [CURLOPT_COOKIEFILE => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 5]);

    return $client;
}

function xsrf(CurlHandle $client): ?string
{
    foreach (curl_getinfo($client, CURLINFO_COOKIELIST) as $cookie) {
        $parts = explode("\t", $cookie);
        if (($parts[5] ?? '') === 'XSRF-TOKEN') {
            return rawurldecode($parts[6]);
        }
    }

    return null;
}

function callApi(CurlHandle $client, string $method, string $path, int $expected, ?array $payload = null, bool $csrf = true): array
{
    global $base, $records;
    $headers = ['Accept: application/json', 'Content-Type: application/json', 'Origin: http://127.0.0.1:8080'];
    if ($csrf && ($token = xsrf($client))) {
        $headers[] = 'X-XSRF-TOKEN: '.$token;
    }
    curl_setopt($client, CURLOPT_HTTPGET, true);
    if ($payload !== null) {
        curl_setopt($client, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt_array($client, [CURLOPT_URL => $base.$path, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers]);
    $body = curl_exec($client);
    $status = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
    $safeRequest = $payload;
    if (isset($safeRequest['password'])) {
        $safeRequest['password'] = '[redacted]';
    }
    $response = json_decode($body ?: '', true);
    $records[] = [
        'timestamp_utc' => gmdate('c'), 'method' => $method, 'path' => $path, 'request' => $safeRequest,
        'expected_status' => $expected, 'actual_status' => $status, 'response' => $response,
        'duration_ms' => round(curl_getinfo($client, CURLINFO_TOTAL_TIME) * 1000, 2),
        'outcome' => $status === $expected ? 'pass' : 'fail',
    ];
    if ($status !== $expected) {
        throw new RuntimeException('Unexpected status for '.$method.' '.$path.'; see sanitized results.');
    }

    return $response ?? [];
}

function loginAs(string $alias): CurlHandle
{
    global $settings, $handles;
    $client = client();
    $handles[] = $client;
    callApi($client, 'GET', '/sanctum/csrf-cookie', 204);
    callApi($client, 'POST', '/api/login', 200, ['email' => $alias.'@example.test', 'password' => $settings['DEMO_PASSWORD']]);

    return $client;
}

$outcome = 'fail';
$failure = null;
$events = [];
try {
    $anonymous = client();
    $handles[] = $anonymous;
    callApi($anonymous, 'GET', '/api/me', 401);
    callApi($anonymous, 'POST', '/api/login', 419, ['email' => 'student.one@example.test', 'password' => $settings['DEMO_PASSWORD']], false);
    $student = loginAs('student.one');
    $advisers = callApi($student, 'GET', '/api/advisers', 200);
    $adviserId = $advisers['data'][0]['id'];
    callApi($student, 'POST', '/api/consultations', 422, ['adviser_id' => $adviserId]);
    $created = callApi($student, 'POST', '/api/consultations', 201, [
        'adviser_id' => $adviserId, 'agenda' => 'Controlled API evidence '.$run.': review fictional methodology.',
    ]);
    $id = $created['data']['id'];
    $start = new DateTimeImmutable('+7 days', new DateTimeZone('UTC'));
    $schedule = ['starts_at' => $start->format('Y-m-d\TH:i:s\Z'), 'ends_at' => $start->modify('+30 minutes')->format('Y-m-d\TH:i:s\Z')];
    callApi($student, 'PATCH', '/api/consultations/'.$id.'/schedule', 403, $schedule);
    $adviser = loginAs('adviser.one');
    callApi($adviser, 'PATCH', '/api/consultations/'.$id.'/schedule', 200, $schedule);
    callApi($adviser, 'PATCH', '/api/consultations/'.$id.'/schedule', 409, $schedule);
    $otherAdviser = loginAs('adviser.two');
    callApi($otherAdviser, 'GET', '/api/consultations/'.$id, 403);
    $panel = loginAs('panel.one');
    callApi($panel, 'GET', '/api/consultations/'.$id, 403);
    $coordinator = loginAs('coordinator.one');
    callApi($coordinator, 'GET', '/api/consultations/'.$id, 200);
    callApi($coordinator, 'PATCH', '/api/consultations/'.$id.'/schedule', 403, $schedule);
    callApi($student, 'POST', '/api/logout', 200, []);
    callApi($student, 'GET', '/api/me', 401);

    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=thesis_consultation_week13;charset=utf8mb4', $settings['DB_USERNAME'], $settings['DB_PASSWORD']);
    $query = $pdo->prepare('SELECT event, actor_id, correlation_id, from_status, to_status, schedule_version FROM consultation_events WHERE consultation_id = ? ORDER BY id');
    $query->execute([$id]);
    $events = $query->fetchAll(PDO::FETCH_ASSOC);
    if (count($events) !== 2) {
        throw new RuntimeException('Expected exactly two audit events for the evidence consultation.');
    }
    $outcome = 'pass';
} catch (Throwable $error) {
    // Do not print driver errors, credentials, cookie headers or full traces.
    $failure = $error instanceof RuntimeException && ! $error instanceof PDOException ? $error->getMessage() : 'Verification stopped; inspect the local service privately.';
} finally {
    foreach ($handles as $handle) {
        curl_close($handle);
    }
}
$result = [
    'run' => $run, 'base_commit' => '27960ba0bee39bc40b003090df61e9782570f895', 'build' => 'uncommitted API milestone',
    'environment' => 'PHP curl -> actual local Laravel HTTP server; MariaDB app database; origin 127.0.0.1:8080',
    'outcome' => $outcome, 'failure' => $failure, 'requests' => $records, 'audit_events' => $events,
    'limitation' => 'Creates and retains one fictional evidence consultation per run; does not prove Flutter UI, concurrent overlap races, notifications or automation. Cookies/secrets omitted.',
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($outcome === 'pass' ? 0 : 1);
