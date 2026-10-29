<?php

use App\Models\AutomationItem;
use App\Models\Consultation;
use App\Services\AutomationDelivery;
use App\Services\AutomationScanner;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$id = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);
$phase = $argv[2] ?? '';
if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'thesis_consultation_week13'
    || ! $id || ! in_array($phase, ['prepare', 'fix'], true)) {
    throw new RuntimeException('Local fictional browser fixture ID and prepare/fix phase required.');
}
$meeting = Consultation::findOrFail($id);
if (! str_starts_with($meeting->agenda, 'Browser evidence:') || $meeting->status !== 'confirmed' || ! $meeting->starts_at->isFuture()) {
    throw new RuntimeException('Only the explicitly identified future browser-test fixture is allowed.');
}
$result = ['timestamp_utc' => gmdate('c'), 'phase' => $phase, 'consultation_id' => $id,
    'environment' => 'Actual local scanner, MariaDB and authenticated controlled receiver; no reset'];
if ($phase === 'prepare') {
    if (AutomationItem::where('consultation_id', $id)->exists()) {
        throw new RuntimeException('Work already exists; preserve it rather than reinitializing evidence.');
    }
    $result['scan'] = app(AutomationScanner::class)->scan();
}
$work = AutomationItem::where('consultation_id', $id)->where('kind', 'reminder')->where('recipient_id', $meeting->student_id)->firstOrFail();
if (($phase === 'prepare' && ($work->status !== 'pending' || $work->attempts !== 0))
    || ($phase === 'fix' && $work->status !== 'needs_review')) {
    throw new RuntimeException('Unexpected fixture state; no reset or replay allowed.');
}
$response = Http::withToken(config('consultation.notification_key'))->timeout(5)->post('http://127.0.0.1:8090/test/control', [
    'work_key' => $work->deduplication_key, 'mode' => $phase === 'prepare' ? 'permanent' : 'success',
]);
if ($response->status() !== 200) {
    throw new RuntimeException('Controlled receiver unavailable; no private diagnostics printed.');
}
$result['work_id'] = $work->id;
if ($phase === 'prepare') {
    $result['delivery'] = app(AutomationDelivery::class)->process($work->id);
    if ($result['delivery']['status'] !== 'needs_review') {
        throw new RuntimeException('Expected controlled permanent review; inspect retained history.');
    }
}
$result['next'] = $phase === 'prepare' ? 'Coordinator reviews in Flutter; fix the controlled receiver before authorizing recovery.' : 'Receiver corrected; coordinator may now authorize recovery through Flutter.';
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
