<?php

use App\Models\AutomationItem;
use App\Models\Consultation;
use App\Models\User;
use App\Services\AutomationDelivery;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'thesis_consultation_week13') {
    throw new RuntimeException('Local fictional Project 29 database only.');
}
$phase = $argv[1] ?? '';
$manifestPath = __DIR__.'/../../evidence/test-results/delivery-live-init.json';
$records = [];

function serviceCall(string $path, array $body, int $expected): array
{
    global $records;
    $client = curl_init('http://127.0.0.1:8090'.$path);
    curl_setopt_array($client, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.config('consultation.notification_key')],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 8]);
    $response = json_decode(curl_exec($client) ?: '', true);
    $status = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
    curl_close($client);
    $records[] = ['service_path' => $path, 'expected_status' => $expected, 'actual_status' => $status, 'response' => $response];
    if ($status !== $expected) {
        throw new RuntimeException('Unexpected controlled service status: '.$status);
    }

    return ['status' => $status, 'response' => $response];
}

function deliveryBody(AutomationItem $work): array
{
    return ['work_key' => $work->deduplication_key, 'correlation_id' => $work->correlation_id,
        'kind' => $work->kind, 'recipient_id' => $work->recipient_id,
        'scheduled_for' => $work->scheduled_for->format('Y-m-d\TH:i:s\Z'), 'data' => $work->payload];
}

function apiCall(CurlHandle $client, string $method, string $path, int $expected, ?array $body = null): array
{
    global $records;
    $headers = ['Content-Type: application/json', 'Accept: application/json', 'Origin: http://127.0.0.1:8080'];
    foreach (curl_getinfo($client, CURLINFO_COOKIELIST) as $cookie) {
        $parts = explode("\t", $cookie);
        if (($parts[5] ?? '') === 'XSRF-TOKEN') {
            $headers[] = 'X-XSRF-TOKEN: '.rawurldecode($parts[6]);
        }
    }
    curl_setopt($client, CURLOPT_HTTPGET, true);
    if ($body !== null) {
        curl_setopt($client, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($client, [CURLOPT_URL => 'http://127.0.0.1:8000'.$path, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    $response = json_decode(curl_exec($client) ?: '', true);
    $status = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
    if (isset($body['password'])) {
        $body['password'] = '[redacted]';
    }
    $records[] = ['timestamp_utc' => gmdate('c'), 'method' => $method, 'path' => $path,
        'request' => $body, 'expected_status' => $expected, 'actual_status' => $status,
        'response' => $response, 'outcome' => $status === $expected ? 'pass' : 'fail'];
    if ($status !== $expected) {
        throw new RuntimeException('Unexpected API status; inspect sanitized record.');
    }

    return $response ?? [];
}

$result = ['phase' => $phase, 'timestamp_utc' => gmdate('c'), 'base_commit' => '0c8a2744a8aaa55e2c6737b3ebb56bee872f5578',
    'build' => 'uncommitted delivery milestone', 'environment' => 'Actual loopback HTTP receiver/API and MariaDB; real clock',
    'outcome' => 'fail'];
try {
    if ($phase === 'init') {
        if (file_exists($manifestPath)) {
            throw new RuntimeException('Existing fixture manifest retained; do not initialize twice.');
        }
        $run = bin2hex(random_bytes(6));
        $student = User::where('email', 'student.one@example.test')->firstOrFail();
        $adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
        $owner = User::where('role', 'coordinator')->orderBy('id')->firstOrFail();
        $fixtures = [];
        foreach (['success', 'transient_once', 'permanent', 'unavailable', 'ack_loss_once', 'timeout_once', 'interrupted'] as $mode) {
            $work = DB::transaction(function () use ($run, $mode, $student, $adviser, $owner) {
                $meeting = Consultation::create(['reference' => 'DELIVERY-'.$run.'-'.$mode,
                    'student_id' => $student->id, 'adviser_id' => $adviser->id, 'agenda' => 'Controlled delivery fixture.',
                    'status' => 'confirmed', 'schedule_version' => 1, 'starts_at' => now()->addHours(2), 'ends_at' => now()->addHours(3)]);

                return AutomationItem::create(['kind' => 'reminder', 'consultation_id' => $meeting->id,
                    'recipient_id' => $student->id, 'review_owner_id' => $owner->id, 'source_version' => 1,
                    'deduplication_key' => hash('sha256', 'reminder:meeting:'.$meeting->id.':1:recipient:'.$student->id),
                    'correlation_id' => (string) Str::uuid(), 'status' => 'pending', 'attempts' => 0,
                    'scheduled_for' => $meeting->starts_at, 'available_at' => now(),
                    'payload' => ['consultation_id' => $meeting->id, 'schedule_version' => 1]]);
            });
            serviceCall('/test/control', ['work_key' => $work->deduplication_key, 'mode' => $mode === 'interrupted' ? 'success' : $mode], 200);
            if ($mode === 'interrupted') {
                // Controlled crash-boundary simulation: receipt accepted, result deliberately not saved.
                // This does not claim an actual process kill or competing-worker execution.
                $token = (string) Str::uuid();
                DB::transaction(function () use ($work, $token) {
                    $work->forceFill(['status' => 'claimed', 'attempts' => 1, 'total_attempts' => 1,
                        'claim_token' => $token, 'claimed_until' => now()->addSeconds(30)])->save();
                    DB::table('automation_attempts')->insert(['automation_item_id' => $work->id, 'cycle' => 1,
                        'number' => 1, 'claim_token' => $token, 'started_at' => now(), 'outcome' => 'started']);
                });
                $result['interrupted_acceptance'] = serviceCall('/notify', deliveryBody($work), 201);
            }
            $fixtures[$mode] = $work->id;
        }
        $result['run'] = $run;
        $result['fixtures'] = $fixtures;
        $result['preconditions'] = 'Seven explicitly initialized fictional confirmed future meetings; no reset, deletion, clock change or force-due update.';
    } else {
        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $fixtures = $manifest['fixtures'];
        $result['fixtures'] = $fixtures;
    }
    if (in_array($phase, ['init', 'tick'], true)) {
        foreach ($fixtures as $mode => $id) {
            $result['worker'][$mode] = app(AutomationDelivery::class)->process($id);
        }
    } elseif ($phase === 'recover') {
        $clients = [];
        foreach (['student.one', 'coordinator.one'] as $alias) {
            $client = curl_init();
            curl_setopt($client, CURLOPT_COOKIEFILE, '');
            $clients[$alias] = $client;
            apiCall($client, 'GET', '/sanctum/csrf-cookie', 204);
            apiCall($client, 'POST', '/api/login', 200, ['email' => $alias.'@example.test', 'password' => config('consultation.demo_password')]);
        }
        $id = $fixtures['permanent'];
        apiCall($clients['student.one'], 'POST', '/api/automation/'.$id.'/recover', 403, ['reason' => 'Unauthorized controlled recovery attempt.']);
        apiCall($clients['coordinator.one'], 'GET', '/api/automation/'.$id, 200);
        apiCall($clients['coordinator.one'], 'POST', '/api/automation/'.$id.'/recover', 422, ['reason' => 'short']);
        $work = AutomationItem::findOrFail($id);
        serviceCall('/test/control', ['work_key' => $work->deduplication_key, 'mode' => 'success'], 200);
        apiCall($clients['coordinator.one'], 'POST', '/api/automation/'.$id.'/recover', 200, ['reason' => 'Controlled service rejection corrected; retry same work identity after review.']);
        $result['worker']['permanent_after_recovery'] = app(AutomationDelivery::class)->process($id);
        apiCall($clients['coordinator.one'], 'POST', '/api/automation/'.$id.'/recover', 409, ['reason' => 'Delivered work must not be manually repeated.']);
        apiCall($clients['student.one'], 'GET', '/api/notifications', 200);
        foreach ($clients as $client) {
            apiCall($client, 'POST', '/api/logout', 200, []);
            curl_close($client);
        }
        $result['api'] = $records;
    } elseif (! in_array($phase, ['snapshot', 'verify'], true)) {
        throw new RuntimeException('Use init, tick, recover or snapshot.');
    }
    $result['items'] = AutomationItem::whereIn('id', $fixtures)->orderBy('id')->get(['id', 'kind', 'status', 'cycle', 'attempts', 'total_attempts', 'available_at', 'claimed_until', 'failure_code', 'receipt']);
    $result['attempts'] = DB::table('automation_attempts')->whereIn('automation_item_id', $fixtures)->orderBy('id')
        ->get(['automation_item_id', 'cycle', 'number', 'started_at', 'finished_at', 'outcome', 'code', 'http_status', 'duration_ms', 'receipt']);
    $pdo = new PDO('sqlite:'.__DIR__.'/../../notification-service/storage/receiver.sqlite');
    foreach ($fixtures as $mode => $id) {
        $work = AutomationItem::findOrFail($id);
        $query = $pdo->prepare('SELECT COUNT(*) AS receipt_count, MAX(receipt) AS receipt FROM receipts WHERE work_key=?');
        $query->execute([$work->deduplication_key]);
        $result['receiver'][$mode] = $query->fetch(PDO::FETCH_ASSOC);
        $query->closeCursor();
    }
    unset($query, $pdo); // Release verification-only SQLite readers before HTTP writes.
    $result['database_counts'] = ['users' => User::count(), 'consultations' => Consultation::count(),
        'automation_items' => AutomationItem::count(), 'attempts' => DB::table('automation_attempts')->count(),
        'recoveries' => DB::table('automation_recoveries')->count()];
    $result['all_work_states'] = AutomationItem::select('kind', 'status')->selectRaw('COUNT(*) AS count')->groupBy('kind', 'status')->get();
    if ($phase === 'verify') {
        foreach ($fixtures as $mode => $id) {
            $work = AutomationItem::findOrFail($id);
            $expectedState = $mode === 'unavailable' ? 'needs_review' : 'delivered';
            $expectedAttempts = $mode === 'unavailable' ? 3 : ($mode === 'success' ? 1 : 2);
            if ($work->status !== $expectedState || $work->total_attempts !== $expectedAttempts
                || (int) $result['receiver'][$mode]['receipt_count'] !== ($mode === 'unavailable' ? 0 : 1)) {
                throw new RuntimeException('Live delivery acceptance check failed.');
            }
        }
        $work = AutomationItem::findOrFail($fixtures['success']);
        $body = deliveryBody($work);
        $result['duplicate_probe'] = serviceCall('/notify', $body, 200);
        $body['recipient_id']++;
        $result['payload_conflict_probe'] = serviceCall('/notify', $body, 409);
        $result['invalid_payload_probe'] = serviceCall('/notify', ['work_key' => str_repeat('0', 64)], 422);
        $client = curl_init('http://127.0.0.1:8090/notify');
        curl_setopt_array($client, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $response = json_decode(curl_exec($client) ?: '', true);
        $status = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        curl_close($client);
        $result['unauthorized_probe'] = ['status' => $status, 'response' => $response];
        if ($status !== 401 || ($result['duplicate_probe']['response']['duplicate'] ?? null) !== true
            || $result['duplicate_probe']['response']['receipt'] !== $work->receipt) {
            throw new RuntimeException('Service boundary acceptance check failed.');
        }
        $before = DB::table('automation_attempts')->count();
        $result['exhausted_repeat'] = app(AutomationDelivery::class)->process($fixtures['unavailable']);
        if (DB::table('automation_attempts')->count() !== $before) {
            throw new RuntimeException('Exhausted item made an extra call.');
        }
        $result['acceptance'] = 'pass';
    }
    $result['outcome'] = 'executed';
} catch (Throwable $error) {
    $result['failure'] = 'Stopped safely; inspect local services and sanitized output. No cleanup performed.';
    $result['api'] = $records;
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($result['outcome'] === 'executed' ? 0 : 1);
