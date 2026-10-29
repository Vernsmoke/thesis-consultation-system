<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationItem extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['claim_token', 'deduplication_key'];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'source_version' => 'integer', 'attempts' => 'integer',
            'cycle' => 'integer', 'total_attempts' => 'integer',
            'scheduled_for' => 'datetime', 'available_at' => 'datetime', 'claimed_until' => 'datetime',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
