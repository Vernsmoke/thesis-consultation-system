<?php

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'thesis_consultation_week13') {
    throw new RuntimeException('Use only the local Project 29 fictional-data environment.');
}

// These are explicitly initialized past-meeting fixtures, not real consultations.
$run = bin2hex(random_bytes(6));
$student = User::where('email', 'student.one@example.test')->firstOrFail();
$adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
$panel = User::where('email', 'panel.one@example.test')->firstOrFail();
$fixtures = DB::transaction(function () use ($run, $student, $adviser, $panel) {
    $meetings = [];
    foreach (['COMPLETE', 'MISSED'] as $kind) {
        $meetings[] = Consultation::create([
            'reference' => 'W13-'.$kind.'-'.$run, 'student_id' => $student->id, 'adviser_id' => $adviser->id,
            'agenda' => 'Controlled past-meeting fixture '.$run.' for HTTP workflow evidence.',
            'status' => 'confirmed', 'starts_at' => now()->subHour(), 'ends_at' => now()->subMinutes(30), 'schedule_version' => 1,
        ]);
    }
    $meetings[0]->panelMembers()->attach($panel->id);

    return $meetings;
});
[$complete, $missed] = $fixtures;
$records = [];
$clients = [];
$events = [];
$outcome = 'fail';
$failure = null;

function workflowCall(CurlHandle $client, string $method, string $path, int $expected, ?array $payload = null): array
{
    global $records;
    $headers = ['Accept: application/json', 'Content-Type: application/json', 'Origin: http://127.0.0.1:8080'];
    foreach (curl_getinfo($client, CURLINFO_COOKIELIST) as $cookie) {
        $parts = explode("\t", $cookie);
        if (($parts[5] ?? '') === 'XSRF-TOKEN') {
            $headers[] = 'X-XSRF-TOKEN: '.rawurldecode($parts[6]);
        }
    }
    curl_setopt($client, CURLOPT_HTTPGET, true);
    if ($payload !== null) {
        curl_setopt($client, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt_array($client, [
        CURLOPT_URL => 'http://127.0.0.1:8000'.$path, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 5,
    ]);
    $body = curl_exec($client);
    $status = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
    $response = json_decode($body ?: '', true);
    $safeRequest = $payload;
    if (isset($safeRequest['password'])) {
        $safeRequest['password'] = '[redacted]';
    }
    $records[] = [
        'timestamp_utc' => gmdate('c'), 'method' => $method, 'path' => $path, 'request' => $safeRequest,
        'expected_status' => $expected, 'actual_status' => $status, 'response' => $response,
        'duration_ms' => round(curl_getinfo($client, CURLINFO_TOTAL_TIME) * 1000, 2),
        'outcome' => $status === $expected ? 'pass' : 'fail',
    ];
    if ($status !== $expected) {
        throw new RuntimeException('Unexpected HTTP status; review the sanitized request record.');
    }

    return $response ?? [];
}

function workflowLogin(string $alias): CurlHandle
{
    global $clients;
    $client = curl_init();
    curl_setopt($client, CURLOPT_COOKIEFILE, '');
    $clients[] = $client;
    workflowCall($client, 'GET', '/sanctum/csrf-cookie', 204);
    workflowCall($client, 'POST', '/api/login', 200, ['email' => $alias.'@example.test', 'password' => config('consultation.demo_password')]);

    return $client;
}

try {
    $studentClient = workflowLogin('student.one');
    $otherStudent = workflowLogin('student.two');
    $adviserClient = workflowLogin('adviser.one');
    $otherAdviser = workflowLogin('adviser.two');
    $panelClient = workflowLogin('panel.one');
    $coordinator = workflowLogin('coordinator.one');
    $status = ['status' => 'completed', 'meeting_notes' => 'Controlled fictional meeting outcome for run '.$run.'.'];
    workflowCall($studentClient, 'PATCH', '/api/consultations/'.$complete->id.'/status', 403, $status);
    workflowCall($otherAdviser, 'PATCH', '/api/consultations/'.$complete->id.'/status', 403, $status);
    workflowCall($adviserClient, 'PATCH', '/api/consultations/'.$complete->id.'/status', 200, $status);
    workflowCall($adviserClient, 'PATCH', '/api/consultations/'.$complete->id.'/status', 409, $status);
    workflowCall($adviserClient, 'POST', '/api/consultations/'.$complete->id.'/action-items', 422, []);
    $assigned = workflowCall($adviserClient, 'POST', '/api/consultations/'.$complete->id.'/action-items', 201, [
        'description' => 'Revise the fictional sampling notes for run '.$run.'.', 'due_at' => now()->addDays(2)->format('Y-m-d\TH:i:s\Z'),
    ]);
    $itemId = $assigned['data']['id'];
    workflowCall($otherStudent, 'PATCH', '/api/action-items/'.$itemId.'/complete', 403);
    workflowCall($adviserClient, 'PATCH', '/api/action-items/'.$itemId.'/complete', 403);
    $first = workflowCall($studentClient, 'PATCH', '/api/action-items/'.$itemId.'/complete', 200);
    $repeat = workflowCall($studentClient, 'PATCH', '/api/action-items/'.$itemId.'/complete', 200);
    if (($first['changed'] ?? null) !== true || ($repeat['changed'] ?? null) !== false || $first['data']['completed_at'] !== $repeat['data']['completed_at']) {
        throw new RuntimeException('Action completion did not preserve its idempotent result.');
    }
    workflowCall($studentClient, 'POST', '/api/consultations/'.$complete->id.'/follow-ups', 201, ['message' => 'Fictional student revision is ready for review, run '.$run.'.']);
    workflowCall($adviserClient, 'POST', '/api/consultations/'.$complete->id.'/follow-ups', 201, ['message' => 'Fictional adviser acknowledged the progress update, run '.$run.'.']);
    workflowCall($panelClient, 'GET', '/api/consultations/'.$complete->id, 200);
    workflowCall($panelClient, 'POST', '/api/consultations/'.$complete->id.'/follow-ups', 403, ['message' => 'Forbidden fictional panel write.']);
    workflowCall($coordinator, 'GET', '/api/consultations/'.$complete->id, 200);
    workflowCall($coordinator, 'POST', '/api/consultations/'.$complete->id.'/follow-ups', 403, ['message' => 'Forbidden fictional coordinator write.']);
    workflowCall($adviserClient, 'PATCH', '/api/consultations/'.$missed->id.'/status', 200, ['status' => 'missed', 'meeting_notes' => 'Controlled fictional missed-session outcome, run '.$run.'.']);
    workflowCall($studentClient, 'POST', '/api/consultations/'.$missed->id.'/follow-ups', 201, ['message' => 'Request a new fictional consultation after the missed meeting, run '.$run.'.']);
    foreach ($clients as $client) {
        workflowCall($client, 'POST', '/api/logout', 200, []);
    }
    $events = DB::table('consultation_events')->whereIn('consultation_id', [$complete->id, $missed->id])
        ->orderBy('id')->get(['consultation_id', 'actor_id', 'event', 'from_status', 'to_status', 'schedule_version', 'action_item_id', 'correlation_id'])->all();
    if (count($events) !== 7) {
        throw new RuntimeException('Expected seven audit events without a duplicate completion event.');
    }
    $outcome = 'pass';
} catch (Throwable $error) {
    $failure = $error instanceof RuntimeException && ! $error instanceof PDOException ? $error->getMessage() : 'Verification stopped; inspect the local service privately.';
} finally {
    foreach ($clients as $client) {
        curl_close($client);
    }
}
echo json_encode([
    'run' => $run, 'base_commit' => 'd2fe52978020f52051dba888773bdb8a62a0a8c9', 'build' => 'uncommitted workflow milestone',
    'environment' => 'Actual loopback Laravel HTTP, Sanctum cookie/CSRF, MariaDB app database',
    'preconditions' => 'Two directly initialized fictional past-confirmed fixtures; one explicit panel link. They were not requested/confirmed via API and are not real meetings.',
    'fixture_ids' => [$complete->id, $missed->id], 'outcome' => $outcome, 'failure' => $failure,
    'requests' => $records, 'audit_events' => $events,
    'limitation' => 'Retains two new fictional consultations and all resulting actions/notes per run. No computer clock changes, automation, real notifications or Flutter workflow demonstrated. Secrets/cookies omitted.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($outcome === 'pass' ? 0 : 1);
