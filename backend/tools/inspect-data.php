<?php

use App\Models\ActionItem;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Read-only verification for the new local app database; never outputs credentials.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'thesis_consultation_week13') {
    throw new RuntimeException('Inspection is limited to the new local Project 29 app database.');
}

$isolation = ['outcome' => 'fail', 'reason' => 'Cross-database access was unexpectedly allowed.'];
try {
    DB::select('SELECT 1 FROM thesis_consultation_week13_test.users LIMIT 1');
} catch (QueryException $error) {
    $driverCode = $error->errorInfo[1] ?? null;
    $isolation = [
        'outcome' => in_array($driverCode, [1044, 1142], true) ? 'pass' : 'fail',
        'sqlstate' => $error->errorInfo[0] ?? null,
        'driver_code' => $driverCode,
        'reason' => 'App account denied access to test database; no data returned.',
    ];
}

$result = [
    'case' => 'DATA-LIVE',
    'timestamp_utc' => now()->toIso8601String(),
    'timezone' => 'UTC; display Asia/Manila',
    'database' => DB::connection()->getDatabaseName(),
    'connection' => DB::selectOne('SELECT VERSION() AS version, CURRENT_USER() AS account'),
    'counts' => [
        'users' => User::count(), 'consultations' => Consultation::count(),
        'panel_links' => DB::table('consultation_panel')->count(), 'action_items' => ActionItem::count(),
        'follow_ups' => DB::table('follow_ups')->count(), 'audit_events' => DB::table('consultation_events')->count(),
        'automation_items' => DB::table('automation_items')->count(),
        'pending_automation' => DB::table('automation_items')->where('status', 'pending')->count(),
        'delivered_automation' => DB::table('automation_items')->where('status', 'delivered')->count(),
    ],
    'roles' => User::select('role')->selectRaw('COUNT(*) AS count')->groupBy('role')->orderBy('role')->get(),
    'consultations' => Consultation::orderBy('reference')->get(['reference', 'status', 'starts_at', 'ends_at', 'schedule_version']),
    'cross_database_isolation' => $isolation,
    'limitation' => 'Read-only counts and controlled record metadata. This snapshot alone does not prove API actions, notification delivery or recovery; consult the dated execution records.',
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($isolation['outcome'] === 'pass' ? 0 : 1);
