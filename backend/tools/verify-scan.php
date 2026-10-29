<?php

use App\Models\ActionItem;
use App\Models\AutomationItem;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'thesis_consultation_week13') {
    throw new RuntimeException('Use only the local Project 29 classroom database.');
}

$run = bin2hex(random_bytes(6));
$student = User::where('email', 'student.one@example.test')->firstOrFail();
$adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
$initialCount = AutomationItem::count();
[$reminder, $elapsed, $action] = DB::transaction(function () use ($run, $student, $adviser) {
    $reminder = Consultation::create([
        'reference' => 'SCAN-REMINDER-'.$run, 'student_id' => $student->id, 'adviser_id' => $adviser->id,
        'agenda' => 'Controlled reminder-scan fixture '.$run.'.', 'status' => 'confirmed', 'schedule_version' => 1,
        'starts_at' => now()->addHour(), 'ends_at' => now()->addMinutes(90),
    ]);
    $elapsed = Consultation::create([
        'reference' => 'SCAN-ELAPSED-'.$run, 'student_id' => $student->id, 'adviser_id' => $adviser->id,
        'agenda' => 'Controlled elapsed-scan fixture '.$run.'.', 'status' => 'confirmed', 'schedule_version' => 1,
        'starts_at' => now()->subHour(), 'ends_at' => now()->subMinutes(30),
    ]);
    $completed = Consultation::where('reference', 'DEMO-COMPLETED-001')->firstOrFail();
    $action = ActionItem::create([
        'consultation_id' => $completed->id, 'assigned_to_id' => $completed->student_id,
        'description' => 'Controlled overdue-scan fixture '.$run.'.', 'due_at' => now()->subHour(), 'deadline_version' => 1,
    ]);

    return [$reminder, $elapsed, $action];
});
$records = [];
$outcome = 'fail';
$failure = null;
try {
    foreach (['first', 'repeat'] as $phase) {
        $exit = Artisan::call('consultations:scan');
        $actual = json_decode(trim(Artisan::output()), true);
        $records[] = ['phase' => $phase, 'timestamp_utc' => gmdate('c'), 'exit_code' => $exit, 'actual' => $actual];
        if ($exit !== 0 || ! is_array($actual) || ($phase === 'first' && $actual['created'] < 9) || ($phase === 'repeat' && $actual['created'] !== 0)) {
            throw new RuntimeException('Scan did not match its expected result; inspect the preserved safe record.');
        }
    }
    if ($elapsed->fresh()->status !== 'missed') {
        throw new RuntimeException('Elapsed fixture was not marked for missed-session review.');
    }
    $beforeScheduler = AutomationItem::count();
    $schedulerExit = Artisan::call('schedule:run', ['--no-interaction' => true]);
    $schedulerOutput = trim(Artisan::output());
    $afterScheduler = AutomationItem::count();
    if ($schedulerExit !== 0 || $beforeScheduler !== $afterScheduler || AutomationItem::sum('attempts') != 0 || AutomationItem::whereNotNull('receipt')->exists()) {
        throw new RuntimeException('Scheduler/duplicate/no-delivery expectation failed.');
    }
    $outcome = 'pass';
} catch (Throwable) {
    $failure = 'Controlled scan verification stopped; inspect safe records and local configuration. No fixture cleanup performed.';
}
echo json_encode([
    'run' => $run, 'base_commit' => '4a51934b36f9010f22989ac1cbdd797e895db725', 'build' => 'uncommitted scan foundation',
    'environment' => 'Actual local Artisan commands and schedule:run; MariaDB app database; no HTTP service calls',
    'preconditions' => 'Two initialized fictional confirmed meetings and one initialized overdue action; no computer-clock change. Existing records preserved.',
    'fixtures' => ['reminder_consultation_id' => $reminder->id, 'elapsed_consultation_id' => $elapsed->id, 'action_item_id' => $action->id],
    'outcome' => $outcome, 'failure' => $failure, 'initial_work_count' => $initialCount, 'scans' => $records,
    'scheduler' => ['exit' => $schedulerExit ?? null, 'output' => $schedulerOutput ?? null, 'before_count' => $beforeScheduler ?? null, 'after_count' => $afterScheduler ?? null],
    'work_by_kind' => AutomationItem::select('kind')->selectRaw('COUNT(*) AS count')->groupBy('kind')->orderBy('kind')->get(),
    'missed_audit' => DB::table('consultation_events')->where('consultation_id', $elapsed->id)->get(['event', 'actor_id', 'actor_type', 'correlation_id']),
    'items' => AutomationItem::orderBy('id')->get(['id', 'kind', 'consultation_id', 'action_item_id', 'recipient_id', 'review_owner_id', 'correlation_id', 'source_version', 'status', 'attempts', 'receipt', 'scheduled_for', 'payload']),
    'limitation' => 'Pending work is not notification delivery. No worker, retry/service failure or recovery demonstrated. Repeating this script retains additional fixtures.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($outcome === 'pass' ? 0 : 1);
