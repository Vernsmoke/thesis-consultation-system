<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(0, 0));
        config(['consultation.demo_password' => 'Test-only-fictional-password']);
        $this->seed();
        $this->withHeader('Origin', 'http://127.0.0.1:8080');
    }

    private function signIn(string $alias): User
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->driver()->flush();
        $user = User::where('email', $alias.'@example.test')->firstOrFail();
        $this->actingAs($user, 'web');

        return $user;
    }

    private function completed(): Consultation
    {
        return Consultation::where('reference', 'DEMO-COMPLETED-001')->firstOrFail();
    }

    private function ended(): Consultation
    {
        $meeting = Consultation::where('reference', 'DEMO-CONFIRMED-001')->firstOrFail();
        $meeting->update(['starts_at' => now()->subHour(), 'ends_at' => now()->subMinutes(30)]);

        return $meeting;
    }

    public function test_request_to_follow_up_normal_path_uses_only_authorized_api_actions(): void
    {
        $student = $this->signIn('student.one');
        $adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
        $id = $this->postJson('/api/consultations', ['adviser_id' => $adviser->id, 'agenda' => 'Discuss fictional sampling and study scope.'])
            ->assertCreated()->json('data.id');
        $this->signIn('adviser.one');
        $this->patchJson('/api/consultations/'.$id.'/schedule', ['starts_at' => '2026-11-01T01:00:00Z', 'ends_at' => '2026-11-01T01:30:00Z'])->assertOk();
        // Advance only the test clock, never the computer clock or production data.
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(2, 0));
        $this->patchJson('/api/consultations/'.$id.'/status', ['status' => 'completed', 'meeting_notes' => 'Fictional meeting: revise the sampling plan.'])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $item = $this->postJson('/api/consultations/'.$id.'/action-items', ['description' => 'Revise the fictional sampling plan.', 'due_at' => '2026-11-03T01:00:00Z'])
            ->assertCreated()->assertJsonPath('data.assigned_to_id', $student->id)->json('data.id');
        $this->signIn('student.one');
        $this->patchJson('/api/action-items/'.$item.'/complete')->assertOk()->assertJsonPath('changed', true);
        $this->postJson('/api/consultations/'.$id.'/follow-ups', ['message' => 'The fictional sampling revision is ready for adviser review.'])->assertCreated();
        $this->signIn('adviser.one');
        $this->getJson('/api/consultations/'.$id)->assertOk()
            ->assertJsonCount(1, 'data.follow_ups')->assertJsonCount(1, 'data.action_items')
            ->assertJsonPath('data.follow_ups.0.author.role', 'student');
        $this->assertDatabaseCount('consultation_events', 6);
    }

    public function test_assigned_adviser_can_record_missed_once_after_the_end(): void
    {
        $id = $this->ended()->id;
        $this->signIn('adviser.one');
        $payload = ['status' => 'missed', 'meeting_notes' => 'Fictional attendee did not join the scheduled meeting.'];
        $this->patchJson('/api/consultations/'.$id.'/status', $payload)->assertOk()->assertJsonPath('data.status', 'missed');
        $this->patchJson('/api/consultations/'.$id.'/status', $payload)->assertStatus(409);
        $this->assertDatabaseCount('consultation_events', 1);
        $this->assertDatabaseHas('consultation_events', ['consultation_id' => $id, 'event' => 'meeting_missed']);
    }

    public function test_future_and_nonconfirmed_meetings_cannot_be_closed(): void
    {
        $this->signIn('adviser.one');
        $payload = ['status' => 'completed', 'meeting_notes' => 'Fictional meeting completion notes.'];
        foreach (['DEMO-CONFIRMED-001', 'DEMO-REQUEST-001', 'DEMO-COMPLETED-001'] as $reference) {
            $meeting = Consultation::where('reference', $reference)->firstOrFail();
            $this->patchJson('/api/consultations/'.$meeting->id.'/status', $payload)->assertStatus(409);
        }
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_students_panel_coordinator_and_other_adviser_cannot_record_outcomes(): void
    {
        $id = $this->ended()->id;
        foreach (['student.one', 'student.two', 'panel.one', 'coordinator.one', 'adviser.two'] as $alias) {
            $this->signIn($alias);
            $this->patchJson('/api/consultations/'.$id.'/status', ['status' => 'completed', 'meeting_notes' => 'Fictional completion notes.'])->assertForbidden();
        }
        $this->assertSame('confirmed', Consultation::findOrFail($id)->status);
    }

    public function test_invalid_outcome_and_missing_notes_are_rejected(): void
    {
        $this->signIn('adviser.one');
        $this->patchJson('/api/consultations/'.$this->ended()->id.'/status', ['status' => 'approved'])
            ->assertUnprocessable()->assertJsonValidationErrors(['status', 'meeting_notes']);
    }

    public function test_action_assignment_requires_assigned_adviser_completed_meeting_and_valid_deadline(): void
    {
        $payload = ['description' => 'Revise the fictional literature review.', 'due_at' => '2026-11-03T01:00:00Z'];
        foreach (['student.one', 'adviser.two', 'panel.one', 'coordinator.one'] as $alias) {
            $this->signIn($alias);
            $this->postJson('/api/consultations/'.$this->completed()->id.'/action-items', $payload)->assertForbidden();
        }
        $this->signIn('adviser.one');
        $this->postJson('/api/consultations/'.$this->ended()->id.'/action-items', $payload)->assertStatus(409);
        $this->postJson('/api/consultations/'.$this->completed()->id.'/action-items', ['description' => 'x', 'due_at' => '2026-01-01T00:00:00Z'])
            ->assertUnprocessable()->assertJsonValidationErrors(['description', 'due_at']);
        $this->postJson('/api/consultations/'.$this->completed()->id.'/action-items', $payload + ['assigned_to_id' => 2, 'completed_at' => '2026-10-29T00:00:00Z'])
            ->assertUnprocessable()->assertJsonValidationErrors(['assigned_to_id', 'completed_at']);
        $this->assertDatabaseCount('action_items', 1);
    }

    public function test_action_completion_is_idempotent_and_only_the_owning_student_can_do_it(): void
    {
        $id = ActionItem::sole()->id;
        foreach (['student.two', 'adviser.one', 'panel.one', 'coordinator.one'] as $alias) {
            $this->signIn($alias);
            $this->patchJson('/api/action-items/'.$id.'/complete')->assertForbidden();
        }
        $this->signIn('student.one');
        $this->patchJson('/api/action-items/'.$id.'/complete')->assertOk()->assertJsonPath('changed', true);
        $completedAt = ActionItem::findOrFail($id)->completed_at;
        $this->travel(1)->hours();
        $this->patchJson('/api/action-items/'.$id.'/complete')->assertOk()->assertJsonPath('changed', false)->assertJsonPath('correlation_id', null);
        $this->assertTrue($completedAt->equalTo(ActionItem::findOrFail($id)->completed_at));
        $this->assertDatabaseCount('consultation_events', 1);
    }

    public function test_follow_up_permissions_content_and_terminal_state_are_enforced(): void
    {
        $id = $this->completed()->id;
        foreach (['student.two', 'adviser.two', 'panel.one', 'coordinator.one'] as $alias) {
            $this->signIn($alias);
            $this->postJson('/api/consultations/'.$id.'/follow-ups', ['message' => 'A fictional progress update.'])->assertForbidden();
        }
        $this->signIn('student.one');
        $this->postJson('/api/consultations/'.$this->ended()->id.'/follow-ups', ['message' => 'A fictional progress update.'])->assertStatus(409);
        $this->postJson('/api/consultations/'.$id.'/follow-ups', ['message' => '', 'author_id' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors(['message', 'author_id']);
        $this->postJson('/api/consultations/'.$id.'/follow-ups', ['message' => 'A fictional student progress update.'])->assertCreated()->assertJsonPath('data.author.role', 'student');
        $this->signIn('adviser.one');
        $this->postJson('/api/consultations/'.$id.'/follow-ups', ['message' => 'A fictional adviser review response.'])->assertCreated();
        $this->getJson('/api/consultations/'.$id)->assertOk()->assertJsonCount(2, 'data.follow_ups');
        $this->assertDatabaseCount('consultation_events', 2);
    }

    public function test_follow_up_after_missed_meeting_does_not_create_action_items(): void
    {
        $meeting = $this->ended();
        $meeting->update(['status' => 'missed']);
        $this->signIn('student.one');
        $this->postJson('/api/consultations/'.$meeting->id.'/follow-ups', ['message' => 'Request another fictional consultation after the missed session.'])->assertCreated();
        $this->assertDatabaseCount('action_items', 1);
    }

    public function test_audit_failure_rolls_back_meeting_status_action_and_follow_up(): void
    {
        $meeting = $this->ended();
        $this->signIn('adviser.one');
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with($query->sql, 'insert') && str_contains($query->sql, '`consultation_events`')) {
                throw new QueryException('mysql', 'controlled audit failure', [], new PDOException('Synthetic audit failure'));
            }
        });
        $this->patchJson('/api/consultations/'.$meeting->id.'/status', ['status' => 'completed', 'meeting_notes' => 'Fictional completion note.'])->assertStatus(503);
        $this->assertSame('confirmed', $meeting->fresh()->status);
        $this->postJson('/api/consultations/'.$this->completed()->id.'/action-items', ['description' => 'A fictional action for audit rollback.', 'due_at' => '2026-11-03T01:00:00Z'])->assertStatus(503);
        $this->postJson('/api/consultations/'.$this->completed()->id.'/follow-ups', ['message' => 'A fictional follow-up for audit rollback.'])->assertStatus(503);
        $this->signIn('student.one');
        $this->patchJson('/api/action-items/'.ActionItem::sole()->id.'/complete')->assertStatus(503);
        $this->assertNull(ActionItem::sole()->completed_at);
        $this->assertDatabaseCount('action_items', 1);
        $this->assertDatabaseCount('follow_ups', 0);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_missing_session_and_missing_action_record_have_safe_errors(): void
    {
        $this->patchJson('/api/action-items/1/complete')->assertUnauthorized();
        $this->signIn('student.one');
        $this->patchJson('/api/action-items/999999/complete')->assertNotFound();
    }
}
