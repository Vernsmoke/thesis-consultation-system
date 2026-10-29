<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\AutomationItem;
use App\Models\Consultation;
use App\Models\User;
use App\Services\AutomationDelivery;
use App\Services\AutomationScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(0, 0));
        config(['consultation.demo_password' => 'Test-only-fictional-password', 'consultation.notification_key' => str_repeat('test', 16)]);
        $this->seed();
        app(AutomationScanner::class)->scan();
        $this->withHeader('Origin', 'http://127.0.0.1:8080');
        Http::preventStrayRequests();
    }

    private function reminder(): AutomationItem
    {
        return AutomationItem::where('kind', 'reminder')->orderBy('id')->firstOrFail();
    }

    public function test_recovery_reports_log_outage_without_losing_the_committed_audit(): void
    {
        $work = $this->reminder();
        $work->update(['status' => 'needs_review', 'failure_code' => 'http_422']);
        $originalCorrelation = $work->correlation_id;
        $this->signIn('coordinator.one');
        Log::shouldReceive('channel')->once()->with('automation')
            ->andThrow(new \RuntimeException('Controlled test-only logging outage.'));

        $response = $this->postJson('/api/automation/'.$work->id.'/recover', [
            'reason' => 'Controlled receiver corrected; investigate unavailable operational log.',
        ])->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.cycle', 2);
        $this->assertSame($originalCorrelation, $work->fresh()->correlation_id);
        $this->assertSame(0, $work->fresh()->attempts);
        $this->assertDatabaseHas('automation_recoveries', [
            'automation_item_id' => $work->id, 'cycle' => 2,
            'actor_id' => User::where('role', 'coordinator')->firstOrFail()->id,
        ]);
        $this->assertDatabaseCount('automation_recoveries', 1);
        $this->assertDatabaseCount('automation_attempts', 0);
        $response->assertJsonPath('log_status', 'unavailable');
    }

    private function success(Request $request)
    {
        return Http::response(['work_key' => $request['work_key'], 'receipt' => 'rcpt_'.str_repeat('a', 32)], 201);
    }

    private function deliver(AutomationItem $work): array
    {
        return app(AutomationDelivery::class)->process($work->id);
    }

    private function signIn(string $alias): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->driver()->flush();
        $this->actingAs(User::where('email', $alias.'@example.test')->firstOrFail(), 'web');
    }

    public function test_success_is_journaled_and_repeat_does_not_make_another_service_call(): void
    {
        Http::fake(fn (Request $request) => $this->success($request));
        $work = $this->reminder();
        $this->assertSame('delivered', $this->deliver($work)['status']);
        $this->assertSame('delivered', $this->deliver($work)['status']);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('automation_attempts', 1);
        $this->assertDatabaseHas('automation_attempts', ['automation_item_id' => $work->id, 'outcome' => 'delivered', 'number' => 1]);
        $this->assertSame(1, $work->fresh()->total_attempts);
    }

    public function test_transient_failure_waits_then_recovers_with_the_same_identity(): void
    {
        $keys = [];
        Http::fake(function (Request $request) use (&$keys) {
            $keys[] = $request['work_key'];

            return count($keys) === 1 ? Http::response([], 503) : $this->success($request);
        });
        $work = $this->reminder();
        $this->assertSame('retry_wait', $this->deliver($work)['status']);
        $this->assertTrue($work->fresh()->available_at->between(now()->addSeconds(30), now()->addSeconds(35)));
        $this->deliver($work);
        Http::assertSentCount(1);
        $this->travel(36)->seconds();
        $this->assertSame('delivered', $this->deliver($work)['status']);
        $this->assertSame($keys[0], $keys[1]);
        $this->assertDatabaseCount('automation_attempts', 2);
    }

    public function test_persistent_transient_failure_stops_after_three_attempts(): void
    {
        Http::fake(fn () => Http::response([], 503));
        $work = $this->reminder();
        foreach ([0, 36, 66] as $seconds) {
            $this->travel($seconds)->seconds();
            $this->deliver($work);
        }
        $this->assertSame('needs_review', $work->fresh()->status);
        $this->assertSame(3, $work->fresh()->attempts);
        $this->deliver($work);
        Http::assertSentCount(3);
        $this->assertDatabaseCount('automation_attempts', 3);
    }

    public function test_permanent_rejection_and_malformed_receipt_require_review(): void
    {
        Http::fakeSequence()->push([], 422)->push(['receipt' => 'bad'], 200);
        $work = $this->reminder();
        $this->assertSame('needs_review', $this->deliver($work)['status']);
        $other = AutomationItem::where('kind', 'reminder')->whereKeyNot($work->id)->firstOrFail();
        $this->assertSame('needs_review', $this->deliver($other)['status']);
        $this->assertSame('invalid_receipt', $other->fresh()->failure_code);
    }

    public function test_connection_timeout_is_a_bounded_persisted_retry(): void
    {
        Http::fake(fn () => Http::failedConnection());
        $work = $this->reminder();
        $this->assertSame('retry_wait', $this->deliver($work)['status']);
        $this->assertSame('connection_or_timeout', $work->fresh()->failure_code);
        $this->assertSame(1, $work->fresh()->attempts);
    }

    public function test_expired_claim_retains_interrupted_history_and_uses_a_new_fenced_attempt(): void
    {
        $work = $this->reminder();
        $oldToken = (string) Str::uuid();
        $work->update(['status' => 'claimed', 'attempts' => 1, 'total_attempts' => 1, 'claim_token' => $oldToken, 'claimed_until' => now()->subSecond()]);
        DB::table('automation_attempts')->insert(['automation_item_id' => $work->id, 'cycle' => 1, 'number' => 1, 'claim_token' => $oldToken, 'started_at' => now()->subMinute(), 'outcome' => 'started']);
        Http::fake(fn (Request $request) => $this->success($request));
        $this->assertSame('delivered', $this->deliver($work)['status']);
        $this->assertDatabaseHas('automation_attempts', ['claim_token' => $oldToken, 'outcome' => 'interrupted']);
        $this->assertSame(2, $work->fresh()->total_attempts);
    }

    public function test_active_claim_is_not_reprocessed_and_late_result_cannot_overwrite_new_owner(): void
    {
        $work = $this->reminder();
        $work->update(['status' => 'claimed', 'claimed_until' => now()->addMinute(), 'claim_token' => (string) Str::uuid()]);
        Http::fake();
        $this->deliver($work);
        Http::assertNothingSent();
        $work->update(['status' => 'pending', 'claimed_until' => null]);
        $replacement = (string) Str::uuid();
        Http::fake(function (Request $request) use ($work, $replacement) {
            $work->fresh()->update(['claim_token' => $replacement]);

            return $this->success($request);
        });
        $result = $this->deliver($work);
        $this->assertSame('late_result_ignored', $result['code']);
        $this->assertSame('claimed', $work->fresh()->status);
        $this->assertSame($replacement, $work->fresh()->claim_token);
        $this->assertNull($work->fresh()->receipt);
    }

    public function test_obsolete_source_and_forged_recipient_or_payload_are_not_sent(): void
    {
        Http::fake();
        $work = $this->reminder();
        $work->consultation->update(['starts_at' => now()->addHours(2), 'ends_at' => now()->addHours(3)]);
        $this->assertSame('cancelled', $this->deliver($work)['status']);
        $other = AutomationItem::where('kind', 'progress_summary')->firstOrFail();
        $other->update(['payload' => ['adviser_id' => [], 'local_date' => '2026-99-99', 'counts' => 'unsafe']]);
        $this->assertSame('needs_review', $this->deliver($other)['status']);
        Http::assertNothingSent();
    }

    public function test_completed_action_cancels_due_work_without_a_call(): void
    {
        $action = ActionItem::sole();
        $action->update(['due_at' => now()->subHour()]);
        app(AutomationScanner::class)->scan();
        $work = AutomationItem::where('kind', 'action_deadline')->firstOrFail();
        $action->update(['completed_at' => now()]);
        Http::fake();
        $this->assertSame('cancelled', $this->deliver($work)['status']);
        Http::assertNothingSent();
    }

    public function test_coordinator_recovery_preserves_history_and_has_a_total_cycle_limit(): void
    {
        $fixed = false;
        Http::fake(function (Request $request) use (&$fixed) {
            return $fixed ? $this->success($request) : Http::response([], 422);
        });
        $work = $this->reminder();
        $this->deliver($work);
        $this->signIn('student.one');
        $this->postJson('/api/automation/'.$work->id.'/recover', ['reason' => 'Corrected fictional test service configuration.'])->assertForbidden();
        $this->signIn('coordinator.one');
        $this->postJson('/api/automation/'.$work->id.'/recover', ['reason' => 'x'])->assertUnprocessable();
        $this->postJson('/api/automation/'.$work->id.'/recover', ['reason' => 'Corrected fictional test service configuration.'])->assertOk()->assertJsonPath('data.cycle', 2)->assertJsonPath('log_status', 'recorded');
        $this->assertDatabaseCount('automation_attempts', 1);
        $this->assertDatabaseCount('automation_recoveries', 1);
        $fixed = true;
        $this->assertSame('delivered', $this->deliver($work)['status']);
        $this->assertSame(2, $work->fresh()->total_attempts);
        $this->postJson('/api/automation/'.$work->id.'/recover', ['reason' => 'Cannot recover delivered work.'])->assertStatus(409);
        $work->update(['status' => 'needs_review', 'receipt' => null, 'cycle' => 3]);
        $this->postJson('/api/automation/'.$work->id.'/recover', ['reason' => 'Recovery cycle limit demonstration.'])->assertStatus(409);
    }

    public function test_review_and_notifications_are_scoped_to_current_roles_and_records(): void
    {
        $this->getJson('/api/automation')->assertUnauthorized();
        $this->signIn('student.two');
        $this->getJson('/api/automation')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/automation/'.$this->reminder()->id)->assertForbidden();
        $this->signIn('panel.one');
        $this->getJson('/api/automation')->assertForbidden();
        $this->signIn('student.one');
        Http::fake(fn (Request $request) => $this->success($request));
        $this->deliver($this->reminder());
        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/automation/'.$this->reminder()->id)->assertOk()->assertJsonMissingPath('data.claim_token')->assertJsonMissingPath('data.deduplication_key');
        $this->signIn('coordinator.one');
        $this->getJson('/api/automation')->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_worker_rejects_external_endpoint_and_bad_batch_limits(): void
    {
        config(['consultation.notification_url' => 'https://unapproved.example/notify']);
        Http::fake();
        $this->artisan('automation:work', ['--id' => $this->reminder()->id])->assertFailed();
        $this->artisan('automation:work', ['--limit' => 51])->assertFailed();
        Http::assertNothingSent();
        $this->assertDatabaseCount('automation_attempts', 0);
    }

    public function test_all_four_work_kinds_revalidate_and_deliver(): void
    {
        $base = Consultation::where('reference', 'DEMO-CONFIRMED-001')->firstOrFail();
        Consultation::create([
            'reference' => 'DELIVERY-ELAPSED', 'student_id' => $base->student_id, 'adviser_id' => $base->adviser_id,
            'agenda' => 'Controlled delivery test fixture.', 'status' => 'confirmed', 'schedule_version' => 1,
            'starts_at' => now()->subHour(), 'ends_at' => now()->subMinutes(30),
        ]);
        ActionItem::sole()->update(['due_at' => now()->subHour()]);
        app(AutomationScanner::class)->scan();
        Http::fake(fn (Request $request) => $this->success($request));
        foreach (AutomationItem::orderBy('id')->get() as $work) {
            $this->assertSame('delivered', $this->deliver($work)['status']);
        }
        $this->assertSame(9, AutomationItem::where('status', 'delivered')->count());
        Http::assertSentCount(9);
    }
}
