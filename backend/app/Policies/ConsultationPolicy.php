<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, User::ROLES, true);
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return match ($user->role) {
            'coordinator' => true,
            'student' => $consultation->student_id === $user->id,
            'adviser' => $consultation->adviser_id === $user->id,
            'panel' => $consultation->panelMembers()->where('users.id', $user->id)->exists(),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    public function confirmSchedule(User $user, Consultation $consultation): bool
    {
        return $user->role === 'adviser' && $consultation->adviser_id === $user->id;
    }
}
