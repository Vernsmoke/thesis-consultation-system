<?php

namespace App\Policies;

use App\Models\AutomationItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AutomationItemPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['student', 'adviser', 'coordinator'], true);
    }

    public function view(User $user, AutomationItem $work): bool
    {
        if ($user->role === 'coordinator') {
            return true;
        }
        if (! in_array($user->role, ['student', 'adviser'], true) || $work->recipient_id !== $user->id) {
            return false;
        }

        return $work->kind === 'progress_summary'
            ? $user->role === 'adviser' && ($work->payload['adviser_id'] ?? null) === $user->id
            : $work->consultation && Gate::forUser($user)->allows('view', $work->consultation);
    }

    public function recover(User $user, AutomationItem $work): bool
    {
        return $user->role === 'coordinator';
    }
}
