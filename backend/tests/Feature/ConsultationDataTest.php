<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsultationDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(0, 0));
        config(['consultation.demo_password' => 'Test-only-fictional-password']);
        $this->seed();
    }

    public function test_seeded_workflow_relationships_and_utc_dates_are_consistent(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('thesis_consultation_week13_test', DB::connection()->getDatabaseName());
        $meeting = Consultation::where('reference', 'DEMO-CONFIRMED-001')->firstOrFail();
        $this->assertSame('student', $meeting->student->role);
        $this->assertSame('adviser', $meeting->adviser->role);
        $this->assertSame('panel', $meeting->panelMembers->sole()->role);
        $this->assertSame('2026-10-30 00:00:00', $meeting->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $meeting->starts_at->timezoneName);
        $item = ActionItem::sole();
        $this->assertSame('completed', $item->consultation->status);
        $this->assertSame($item->consultation->student_id, $item->assigned_to_id);
        $this->assertNull($item->completed_at);
    }

    public function test_reseeding_does_not_duplicate_or_overwrite_work(): void
    {
        $meeting = Consultation::where('reference', 'DEMO-REQUEST-001')->firstOrFail();
        $meeting->update(['agenda' => 'Student-edited fictional agenda.']);
        $item = ActionItem::sole();
        $item->update(['completed_at' => now()]);
        $passwordHash = User::where('email', 'student.one@example.test')->value('password');
        $this->seed();
        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('consultations', 3);
        $this->assertDatabaseCount('consultation_panel', 1);
        $this->assertDatabaseCount('action_items', 1);
        $this->assertSame('Student-edited fictional agenda.', $meeting->fresh()->agenda);
        $this->assertNotNull($item->fresh()->completed_at);
        $this->assertSame($passwordHash, User::where('email', 'student.one@example.test')->value('password'));
    }

    public function test_demo_passwords_are_hashed_and_not_serialized(): void
    {
        $user = User::where('email', 'student.one@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Test-only-fictional-password', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertFalse($user->isFillable('role'));
        $this->assertEqualsCanonicalizing(User::ROLES, User::distinct()->pluck('role')->all());
    }

    public function test_unknown_user_reference_cannot_create_an_orphan_action_item(): void
    {
        $this->expectException(QueryException::class);
        ActionItem::create([
            'consultation_id' => Consultation::firstOrFail()->id,
            'assigned_to_id' => 999999,
            'description' => 'Invalid fictional assignee.',
            'due_at' => now()->addDay(),
        ]);
    }

    public function test_database_rejects_a_confirmed_meeting_without_a_schedule(): void
    {
        $this->expectException(QueryException::class);
        Consultation::where('reference', 'DEMO-REQUEST-001')->update(['status' => 'confirmed']);
    }

    public function test_database_rejects_an_end_before_the_start(): void
    {
        $this->expectException(QueryException::class);
        Consultation::where('reference', 'DEMO-CONFIRMED-001')->update(['ends_at' => now()->subDay()]);
    }

    public function test_database_rejects_a_duplicate_panel_assignment(): void
    {
        $meeting = Consultation::where('reference', 'DEMO-CONFIRMED-001')->firstOrFail();
        $this->expectException(QueryException::class);
        $meeting->panelMembers()->attach($meeting->panelMembers->sole()->id);
    }

    public function test_referenced_student_cannot_be_deleted(): void
    {
        $this->expectException(QueryException::class);
        User::where('email', 'student.one@example.test')->firstOrFail()->delete();
    }

    public function test_database_rejects_an_unknown_role(): void
    {
        $this->expectException(QueryException::class);
        User::where('email', 'student.one@example.test')->update(['role' => 'administrator']);
    }
}
