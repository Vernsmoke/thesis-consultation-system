<?php

namespace Database\Seeders;

use App\Models\ActionItem;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('consultation.demo_password');
        if (! app()->environment(['local', 'testing']) || ! is_string($password) || strlen($password) < 16) {
            throw new RuntimeException('Fictional seeding requires local/testing and a private DEMO_PASSWORD of at least 16 characters.');
        }

        DB::transaction(function () use ($password) {
            $accounts = [
                'student.one' => ['Student One (Demo)', 'student'],
                'student.two' => ['Student Two (Demo)', 'student'],
                'adviser.one' => ['Adviser One (Demo)', 'adviser'],
                'adviser.two' => ['Adviser Two (Demo)', 'adviser'],
                'panel.one' => ['Panel One (Demo)', 'panel'],
                'coordinator.one' => ['Coordinator One (Demo)', 'coordinator'],
            ];
            $users = [];
            foreach ($accounts as $alias => [$name, $role]) {
                $user = User::firstOrCreate(['email' => $alias.'@example.test'], [
                    'name' => $name, 'password' => $password,
                ]);
                // Role is deliberately excluded from public mass assignment.
                if ($user->wasRecentlyCreated) {
                    $user->forceFill(['role' => $role])->save();
                }
                if ($user->role !== $role) {
                    throw new RuntimeException('An existing demo account has a different role; review it manually.');
                }
                $users[$alias] = $user;
            }

            Consultation::firstOrCreate(['reference' => 'DEMO-REQUEST-001'], [
                'student_id' => $users['student.two']->id,
                'adviser_id' => $users['adviser.one']->id,
                'agenda' => 'Discuss the fictional study scope and proposed research questions.',
                'status' => 'requested',
            ]);
            $confirmed = Consultation::firstOrCreate(['reference' => 'DEMO-CONFIRMED-001'], [
                'student_id' => $users['student.one']->id,
                'adviser_id' => $users['adviser.one']->id,
                'agenda' => 'Review the fictional literature review outline.',
                'status' => 'confirmed',
                'starts_at' => now()->addDay()->startOfHour(),
                'ends_at' => now()->addDay()->startOfHour()->addMinutes(30),
                'schedule_version' => 1,
            ]);
            $completed = Consultation::firstOrCreate(['reference' => 'DEMO-COMPLETED-001'], [
                'student_id' => $users['student.one']->id,
                'adviser_id' => $users['adviser.one']->id,
                'agenda' => 'Review the fictional methodology draft.',
                'status' => 'completed',
                'starts_at' => now()->subDay()->startOfHour(),
                'ends_at' => now()->subDay()->startOfHour()->addMinutes(30),
                'schedule_version' => 1,
                'meeting_notes' => 'Fictional fixture: revise the sampling explanation.',
            ]);
            $confirmed->panelMembers()->syncWithoutDetaching([$users['panel.one']->id]);
            ActionItem::firstOrCreate([
                'consultation_id' => $completed->id,
                'description' => 'Revise the fictional sampling explanation.',
            ], [
                'assigned_to_id' => $users['student.one']->id,
                'due_at' => now()->addDays(2)->startOfHour(),
                'deadline_version' => 1,
            ]);
        });
    }
}
