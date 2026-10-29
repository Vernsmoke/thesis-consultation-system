<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\AutomationItem;
use App\Models\Consultation;
use App\Models\User;
use App\Services\AutomationScanner;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class AutomationScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(0, 0));
        config(['consultation.demo_password' => 'Test-only-fictional-password']);
        $this->seed();
    }

    private function elapsed(): Consultation
    {
        $base = Consultation::where('reference', 'DEMO-CONFIRMED-001')->firstOrFail();

        return Consultation::create([
            'reference' => 'SCAN-ELAPSED', 'student_id' => $base->student_id, 'adviser_id' => $base->adviser_id,
            'agenda' => 'Fictional elapsed session fixture.', 'status' => 'confirmed', 'schedule_version' => 1,
            'starts_at' => now()->subHour(), 'ends_at' => now()->subMinutes(30),
        ]);
    }

    public function test_all_four_kinds_create_minimal_work_and_repeat_without_duplicates(): void
    {
        $elapsed = $this->elapsed();
        ActionItem::sole()->update(['due_at' => now()->subHour()]);
        $scan = app(AutomationScanner::class)->scan();
        $this->assertSame(9, $scan['created']);
        $this->assertSame(1, $scan['missed_detected']);
        $this->assertSame('missed', $elapsed->fresh()->status);
        foreach (['reminder' => 2, 'missed_session' => 3, 'action_deadline' => 2, 'progress_summary' => 2] as $kind => $count) {
            $this->assertSame($count, AutomationItem::where('kind', $kind)->count());
        }
        $event = DB::table('consultation_events')->where('event', 'elapsed_meeting_detected')->sole();
        $this->assertNull($event->actor_id);
        $this->assertSame('automation', $event->actor_type);
        $this->assertSame(0, app(AutomationScanner::class)->scan()['created']);
        $this->assertDatabaseCount('consultation_events', 1);
        $this->assertDatabaseCount('automation_items', 9);
        $this->assertSame(0, AutomationItem::sum('attempts'));
        $payload = AutomationItem::where('kind', 'progress_summary')->firstOrFail()->payload;
        $this->assertSame(['requested' => 1, 'confirmed' => 1, 'completed' => 1, 'missed' => 1, 'open_overdue_actions' => 1], $payload['counts']);
        $this->assertStringNotContainsString('agenda', json_encode(AutomationItem::pluck('payload')));
        $this->assertStringNotContainsString('password', json_encode(AutomationItem::pluck('payload')));
    }

    public function test_completed_and_future_actions_are_not_queued(): void
    {
        $this->assertSame(0, AutomationItem::count());
        app(AutomationScanner::class)->scan();
        $this->assertSame(0, AutomationItem::where('kind', 'action_deadline')->count());
        ActionItem::sole()->update(['due_at' => now()->subHour(), 'completed_at' => now()]);
        app(AutomationScanner::class)->scan();
        $this->assertSame(0, AutomationItem::where('kind', 'action_deadline')->count());
    }

    public function test_reminders_use_future_window_and_do_not_process_completed_meetings(): void
    {
        Consultation::where('status', 'confirmed')->update(['starts_at' => now()->addHours(25), 'ends_at' => now()->addHours(26)]);
        app(AutomationScanner::class)->scan();
        $this->assertSame(0, AutomationItem::where('kind', 'reminder')->count());
        $this->assertSame(0, AutomationItem::where('kind', 'missed_session')->count());
    }

    public function test_daily_summary_rolls_over_at_manila_midnight_not_utc_midnight(): void
    {
        $this->travelTo(now()->setTime(15, 59));
        app(AutomationScanner::class)->scan();
        $this->travel(1)->minutes();
        $second = app(AutomationScanner::class)->scan();
        $this->assertSame(2, $second['created']);
        $this->assertSame(4, AutomationItem::where('kind', 'progress_summary')->count());
        $this->assertSame(['2026-10-29', '2026-10-30'], AutomationItem::where('kind', 'progress_summary')->get()->pluck('payload.local_date')->unique()->values()->all());
    }

    public function test_invalid_source_roles_are_routed_to_coordinator_without_state_change(): void
    {
        $elapsed = $this->elapsed();
        $elapsed->update(['student_id' => $elapsed->adviser_id]);
        app(AutomationScanner::class)->scan();
        $review = AutomationItem::where('status', 'needs_review')->sole();
        $this->assertSame('invalid_source_record', $review->failure_code);
        $this->assertSame(User::where('role', 'coordinator')->value('id'), $review->recipient_id);
        $this->assertSame('confirmed', $elapsed->fresh()->status);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_missing_coordinator_fails_visibly_without_work(): void
    {
        User::where('role', 'coordinator')->update(['role' => 'panel']);
        $this->artisan('consultations:scan')->expectsOutputToContain('missing_review_owner')->assertFailed();
        $this->assertDatabaseCount('automation_items', 0);
    }

    public function test_capacity_limit_rolls_back_preceding_missed_state_and_work(): void
    {
        $elapsed = $this->elapsed();
        config(['consultation.scan_limit' => 1]);
        $base = Consultation::where('reference', 'DEMO-COMPLETED-001')->firstOrFail();
        ActionItem::sole()->update(['due_at' => now()->subHour()]);
        ActionItem::create(['consultation_id' => $base->id, 'assigned_to_id' => $base->student_id, 'description' => 'Second fictional overdue task.', 'due_at' => now()->subHour()]);
        $this->artisan('consultations:scan')->expectsOutputToContain('scan_capacity_exceeded')->assertFailed();
        $this->assertSame('confirmed', $elapsed->fresh()->status);
        $this->assertDatabaseCount('automation_items', 0);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_storage_failure_late_in_scan_rolls_back_all_changes(): void
    {
        $elapsed = $this->elapsed();
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with($query->sql, 'insert') && str_contains($query->sql, '`automation_items`') && in_array('progress_summary', $query->bindings, true)) {
                throw new QueryException('mysql', 'controlled storage failure', [], new PDOException('Synthetic scan failure'));
            }
        });
        $this->artisan('consultations:scan')->expectsOutputToContain('scan_storage_failure')->assertFailed();
        $this->assertSame('confirmed', $elapsed->fresh()->status);
        $this->assertDatabaseCount('automation_items', 0);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_log_failure_is_reported_after_commit_without_claiming_a_rollback(): void
    {
        $channel = \Mockery::mock();
        $channel->shouldReceive('info')->andThrow(new RuntimeException('Synthetic log failure'));
        Log::shouldReceive('channel')->with('automation')->andReturn($channel);
        $this->artisan('consultations:scan')->expectsOutputToContain('Scan committed; automation log unavailable')->assertFailed();
        $this->assertDatabaseCount('automation_items', 4);
    }

    public function test_duplicate_identity_is_enforced_by_the_database(): void
    {
        app(AutomationScanner::class)->scan();
        $item = AutomationItem::firstOrFail();
        $this->expectException(QueryException::class);
        $copy = $item->replicate();
        $copy->correlation_id = (string) Str::uuid();
        $copy->save();
    }
}
