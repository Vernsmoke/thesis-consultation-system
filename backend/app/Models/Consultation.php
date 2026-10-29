<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    protected $fillable = [
        'reference', 'student_id', 'adviser_id', 'agenda', 'status',
        'starts_at', 'ends_at', 'schedule_version', 'meeting_notes',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'schedule_version' => 'integer'];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'student' => $query->where('student_id', $user->id),
            'adviser' => $query->where('adviser_id', $user->id),
            'panel' => $query->whereHas('panelMembers', fn (Builder $members) => $members->where('users.id', $user->id)),
            'coordinator' => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function panelMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'consultation_panel');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }
}
