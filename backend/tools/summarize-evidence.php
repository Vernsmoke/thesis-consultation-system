<?php

// Derive bounded exercise indicators from saved evidence, never the live DB.
$root = dirname(__DIR__, 2);
$read = fn (string $path) => json_decode(file_get_contents($root.'/'.$path), true, flags: JSON_THROW_ON_ERROR);
$delivery = $read('evidence/test-results/delivery-live-verified.json');
$browser = $read('evidence/test-results/flutter-browser-checks.json');
$snapshot = $read('evidence/test-results/flutter-after-app-snapshot.json');
$attempts = $delivery['attempts'];
$durations = array_values(array_filter(array_column($attempts, 'duration_ms'), fn ($value) => $value !== null));
sort($durations);
$httpFailures = count(array_filter($attempts, fn ($a) => ($a['http_status'] ?? 0) >= 400));
$timeouts = count(array_filter($attempts, fn ($a) => $a['code'] === 'connection_or_timeout'));
$interrupted = count(array_filter($attempts, fn ($a) => $a['outcome'] === 'interrupted'));
$started = array_map(fn ($a) => strtotime($a['started_at'].' UTC'), $attempts);
$finished = array_map(fn ($a) => strtotime($a['finished_at'].' UTC'), $attempts);
$elapsed = max($finished) - min($started);
$accepted = count(array_filter($delivery['items'], fn ($item) => $item['status'] === 'delivered'));
$httpAttempts = count($attempts) - $interrupted;
$source = [];
foreach (['backend/app/Services', 'backend/app/Http/Controllers', 'frontend/lib'] as $folder) {
    foreach (glob($root.'/'.$folder.'/*') as $path) {
        if (is_file($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), ['php', 'dart'], true)) {
            $source[] = ['path' => str_replace('\\', '/', substr($path, strlen($root) + 1)),
                'physical_lines' => count(file($path)), 'sha256' => hash_file('sha256', $path)];
        }
    }
}
echo json_encode([
    'derived_at_utc' => gmdate('c'), 'method' => 'Read-only derivation from named retained JSON evidence; not a new load test or live queue snapshot.',
    'delivery_source' => 'evidence/test-results/delivery-live-verified.json',
    'cohort' => 'Seven deliberately faulted delivery fixtures, work12-18; one manually initialized interruption boundary',
    'cases' => count($delivery['items']), 'accepted_cases' => $accepted,
    'accepted_case_percent' => round(100 * $accepted / count($delivery['items']), 2),
    'journal_entries' => count($attempts), 'interrupted_entries' => $interrupted,
    'recorded_worker_http_attempts' => $httpAttempts, 'http_error_responses' => $httpFailures, 'connection_timeouts' => $timeouts,
    'failed_http_attempt_percent' => round(100 * ($httpFailures + $timeouts) / $httpAttempts, 2),
    'extra_journal_entries_after_first' => count($attempts) - count($delivery['items']),
    'elapsed_exercise_seconds' => $elapsed,
    'observed_acceptances_per_minute' => round(60 * $accepted / $elapsed, 3),
    'recorded_http_duration_ms' => ['samples' => count($durations), 'mean' => round(array_sum($durations) / count($durations), 2),
        'median' => $durations[intdiv(count($durations), 2)], 'maximum' => max($durations)],
    'receiver_receipts_per_case' => array_map(fn ($receipt) => $receipt['receipt_count'], $delivery['receiver']),
    'browser_recorded_cases' => count($browser['checks']),
    'historical_queue_snapshot_utc' => $snapshot['timestamp_utc'], 'historical_counts' => $snapshot['counts'],
    'source_indicators' => $source,
    'limits' => ['Fault-heavy cohort is not normal-use reliability or an SLA estimate.',
        'Exercise rate includes backoff, a manual recovery and operator gaps; it is not sustainable throughput.',
        'No CPU/memory/load/concurrent-worker measurements were taken.',
        'Physical lines are navigation/change-impact indicators, not proof of maintainability.',
        'Counts are historical snapshots; no new scan, worker or app record change performed.'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
