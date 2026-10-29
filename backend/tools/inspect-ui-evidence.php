<?php

use App\Models\ActionItem;
use App\Models\AutomationItem;
use App\Models\Consultation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'thesis_consultation_week13') {
    throw new RuntimeException('Read-only local Project 29 evidence inspection only.');
}
$requestId = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);
$outcomeId = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
$workId = filter_var($argv[3] ?? null, FILTER_VALIDATE_INT);
if (! $requestId || ! $outcomeId || ! $workId) {
    throw new RuntimeException('Provide the retained browser request, elapsed outcome and recovered work IDs.');
}
$request = Consultation::findOrFail($requestId);
$outcome = Consultation::findOrFail($outcomeId);
$work = AutomationItem::findOrFail($workId);
$attempts = DB::table('automation_attempts')->where('automation_item_id', $workId)->orderBy('id')
    ->get(['cycle', 'number', 'outcome', 'code', 'http_status', 'receipt', 'started_at', 'finished_at']);
$recoveries = DB::table('automation_recoveries')->where('automation_item_id', $workId)
    ->get(['actor_id', 'cycle', 'created_at']);
$pdo = new PDO('sqlite:'.__DIR__.'/../../notification-service/storage/receiver.sqlite');
$query = $pdo->prepare('SELECT COUNT(*) AS receipt_count, MAX(receipt) AS receipt FROM receipts WHERE work_key=?');
$query->execute([$work->deduplication_key]);
$receipt = $query->fetch(PDO::FETCH_ASSOC);
$query->closeCursor();
$checks = [
    'browser_request_confirmed' => str_starts_with($request->agenda, 'Browser evidence:') && $request->status === 'confirmed' && $request->schedule_version === 1,
    'elapsed_fixture_completed' => $outcome->status === 'completed',
    'browser_action_completed' => ActionItem::where('consultation_id', $outcomeId)->where('description', 'like', 'Browser test:%')->whereNotNull('completed_at')->exists(),
    'browser_follow_up_retained' => DB::table('follow_ups')->where('consultation_id', $outcomeId)->where('message', 'like', 'Browser test adviser follow-up:%')->exists(),
    'recovery_kept_original_failure' => $attempts->count() === 2 && $attempts[0]->code === 'http_422' && $attempts[1]->outcome === 'delivered',
    'bounded_recovery_and_one_receipt' => $work->status === 'delivered' && $work->cycle === 2 && $work->total_attempts === 2 && $recoveries->count() === 1 && (int) $receipt['receipt_count'] === 1 && $receipt['receipt'] === $work->receipt,
    'older_exhausted_item_unchanged' => AutomationItem::whereKey(15)->where('status', 'needs_review')->where('cycle', 1)->where('total_attempts', 3)->exists(),
];
echo json_encode([
    'timestamp_utc' => gmdate('c'), 'base_commit' => '297f26607e9e42b629b6d4911d4224bd12fbd5e5', 'build' => 'uncommitted checkpoint07',
    'environment' => 'Read-only MariaDB and receiver ledger verification after actual Flutter browser actions',
    'consultations' => Consultation::whereIn('id', [$requestId, $outcomeId])->get(['id', 'reference', 'status', 'starts_at', 'ends_at', 'schedule_version']),
    'actions' => ActionItem::where('consultation_id', $outcomeId)->get(['id', 'due_at', 'completed_at', 'assigned_to_id']),
    'audit' => DB::table('consultation_events')->whereIn('consultation_id', [$requestId, $outcomeId])->orderBy('id')
        ->get(['consultation_id', 'actor_id', 'actor_type', 'event', 'from_status', 'to_status', 'action_item_id', 'correlation_id', 'created_at']),
    'work' => $work->only(['id', 'consultation_id', 'correlation_id', 'status', 'cycle', 'attempts', 'total_attempts', 'receipt']),
    'attempts' => $attempts, 'recoveries' => $recoveries, 'receiver' => $receipt,
    'checks' => $checks, 'outcome' => in_array(false, $checks, true) ? 'fail' : 'pass',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
