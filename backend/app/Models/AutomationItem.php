<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'source_version' => 'integer', 'attempts' => 'integer',
            'scheduled_for' => 'datetime', 'available_at' => 'datetime', 'claimed_until' => 'datetime',
        ];
    }
}
