<?php

namespace App\Policies;

use App\Models\ActionItem;
use App\Models\User;

class ActionItemPolicy
{
    public function complete(User $user, ActionItem $item): bool
    {
        return $user->role === 'student' && $item->assigned_to_id === $user->id
            && $item->consultation->student_id === $user->id;
    }
}
