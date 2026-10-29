<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationApiTest extends TestCase
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

    private function requested(): Consultation
    {
        return Consultation::where('reference', 'DEMO-REQUEST-001')->firstOrFail();
    }

    private function schedule(): array
    {
        return ['starts_at' => '2026-11-01T01:00:00Z', 'ends_at' => '2026-11-01T01:30:00Z'];
    }

    public function test_login_uses_server_role_and_does_not_return_a_password_or_token(): void
    {
        $this->postJson('/api/login', ['email' => 'student.one@example.test', 'password' => 'Test-only-fictional-password', 'role' => 'coordinator'])
            ->assertOk()->assertJsonPath('data.role', 'student')->assertJsonMissingPath('data.password')->assertJsonMissingPath('token');
        $this->assertAuthenticated('web');
    }

    public function test_invalid_login_is_generic_and_unauthenticated_reads_are_401(): void
    {
        $this->postJson('/api/login', ['email' => 'student.one@example.test', 'password' => 'wrong'])
            ->assertUnauthorized()->assertExactJson(['message' => 'Invalid email or password.']);
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/consultations')->assertUnauthorized();
    }

    public function test_login_rate_limit_stops_repeated_password_attempts(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/login', ['email' => 'missing@example.test', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/login', ['email' => 'missing@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_logout_ends_the_session(): void
    {
        $this->signIn('student.one');
        $this->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_student_reads_only_own_records_and_cannot_open_another_students_record(): void
    {
        $this->signIn('student.two');
        $this->getJson('/api/consultations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.reference', 'DEMO-REQUEST-001');
        $other = Consultation::where('reference', 'DEMO-CONFIRMED-001')->firstOrFail();
        $this->getJson('/api/consultations/'.$other->id)->assertForbidden();
        $this->getJson('/api/consultations/999999')->assertNotFound();
    }

    public function test_unassigned_adviser_has_no_records_and_cannot_read_others(): void
    {
        $this->signIn('adviser.two');
        $this->getJson('/api/consultations')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/consultations/'.$this->requested()->id)->assertForbidden();
    }

    public function test_panel_is_read_only_and_sees_only_explicit_membership(): void
    {
        $this->signIn('panel.one');
        $this->getJson('/api/consultations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.reference', 'DEMO-CONFIRMED-001');
        $this->getJson('/api/consultations/'.$this->requested()->id)->assertForbidden();
        $this->postJson('/api/consultations', ['agenda' => 'A valid fictional agenda.', 'adviser_id' => 3])->assertForbidden();
        $this->getJson('/api/advisers')->assertForbidden();
    }

    public function test_coordinator_can_review_but_cannot_confirm_for_the_adviser(): void
    {
        $this->signIn('coordinator.one');
        $this->getJson('/api/consultations')->assertOk()->assertJsonCount(3, 'data');
        $this->patchJson('/api/consultations/'.$this->requested()->id.'/schedule', $this->schedule())->assertForbidden();
    }

    public function test_student_request_and_assigned_adviser_confirmation_are_audited(): void
    {
        $student = $this->signIn('student.one');
        $adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
        $this->getJson('/api/advisers')->assertOk()->assertJsonCount(2, 'data');
        $created = $this->postJson('/api/consultations', ['agenda' => 'Review a fictional research outline.', 'adviser_id' => $adviser->id])
            ->assertCreated()->assertJsonPath('data.student_id', $student->id)->assertJsonPath('data.status', 'requested');
        $id = $created->json('data.id');
        $this->assertDatabaseHas('consultation_events', ['consultation_id' => $id, 'actor_id' => $student->id, 'event' => 'consultation_requested']);
        $this->signIn('adviser.one');
        $this->patchJson('/api/consultations/'.$id.'/schedule', $this->schedule())
            ->assertOk()->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.schedule_version', 1);
        $this->assertDatabaseHas('consultation_events', ['consultation_id' => $id, 'actor_id' => $adviser->id, 'event' => 'schedule_confirmed']);
        $this->assertDatabaseCount('consultation_events', 2);
        $this->patchJson('/api/consultations/'.$id.'/schedule', $this->schedule())->assertStatus(409);
        $this->assertDatabaseCount('consultation_events', 2);
    }

    public function test_request_validation_rejects_missing_agenda_nonadviser_and_impersonation(): void
    {
        $this->signIn('student.one');
        $student = User::where('email', 'student.two@example.test')->firstOrFail();
        $this->postJson('/api/consultations', ['adviser_id' => $student->id])->assertUnprocessable()->assertJsonValidationErrors(['agenda', 'adviser_id']);
        $adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
        $this->postJson('/api/consultations', ['adviser_id' => $adviser->id, 'agenda' => 'A valid fictional agenda.', 'student_id' => $student->id, 'status' => 'completed'])
            ->assertUnprocessable()->assertJsonValidationErrors(['student_id', 'status']);
        $this->assertDatabaseCount('consultations', 3);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_student_and_other_adviser_cannot_confirm_a_schedule(): void
    {
        foreach (['student.two', 'adviser.two'] as $alias) {
            $this->signIn($alias);
            $this->patchJson('/api/consultations/'.$this->requested()->id.'/schedule', $this->schedule())->assertForbidden();
        }
        $this->assertSame('requested', $this->requested()->status);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_invalid_past_reversed_and_ambiguous_dates_are_rejected(): void
    {
        $this->signIn('adviser.one');
        foreach ([
            ['starts_at' => '2026-10-01T01:00:00Z', 'ends_at' => '2026-10-01T01:30:00Z'],
            ['starts_at' => '2026-11-01T01:00:00Z', 'ends_at' => '2026-11-01T00:30:00Z'],
            ['starts_at' => '2026-11-01 01:00:00', 'ends_at' => '2026-11-01 01:30:00'],
        ] as $schedule) {
            $this->patchJson('/api/consultations/'.$this->requested()->id.'/schedule', $schedule)->assertUnprocessable();
        }
        $this->assertSame('requested', $this->requested()->status);
    }

    public function test_conflicting_adviser_schedule_is_rejected_without_mutation(): void
    {
        $this->signIn('adviser.one');
        $this->patchJson('/api/consultations/'.$this->requested()->id.'/schedule', [
            'starts_at' => '2026-10-30T00:00:00Z', 'ends_at' => '2026-10-30T00:30:00Z',
        ])->assertStatus(409);
        $this->assertSame('requested', $this->requested()->status);
        $this->assertDatabaseCount('consultation_events', 0);
    }

    public function test_database_failure_returns_a_safe_503_without_creating_work(): void
    {
        $this->signIn('student.one');
        $adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
        $manager = \Mockery::mock(DB::getFacadeRoot());
        $manager->shouldReceive('transaction')->once()->andThrow(new QueryException('mysql', 'insert private payload', ['secret-test-value'], new \PDOException('Simulated unavailable dependency')));
        DB::swap($manager);
        $this->postJson('/api/consultations', ['adviser_id' => $adviser->id, 'agenda' => 'A valid fictional agenda.'])
            ->assertStatus(503)->assertExactJson(['message' => 'The data service is unavailable. Please retry later.']);
        $this->assertDatabaseCount('consultations', 3);
    }

    public function test_an_audit_write_failure_rolls_back_the_consultation_too(): void
    {
        $this->signIn('student.one');
        $adviser = User::where('email', 'adviser.one@example.test')->firstOrFail();
        DB::listen(function (QueryExecuted $query): void {
            if (str_starts_with($query->sql, 'insert') && str_contains($query->sql, '`consultation_events`')) {
                throw new QueryException('mysql', 'controlled audit failure', [], new \PDOException('Synthetic audit failure'));
            }
        });
        $this->postJson('/api/consultations', ['adviser_id' => $adviser->id, 'agenda' => 'A valid fictional agenda.'])->assertStatus(503);
        $this->assertDatabaseCount('consultations', 3);
        $this->assertDatabaseCount('consultation_events', 0);
    }
}
