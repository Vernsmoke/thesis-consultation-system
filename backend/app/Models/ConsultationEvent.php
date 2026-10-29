<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'consultation_id', 'actor_id', 'correlation_id', 'event', 'from_status',
        'to_status', 'schedule_version', 'action_item_id', 'created_at', 'actor_type',
    ];

    // Call inside the same transaction as the state change. Never include note text.
    public static function record(Consultation $meeting, User $actor, string $event, ?string $from, string $correlation, ?int $actionItemId = null): void
    {
        static::create([
            'consultation_id' => $meeting->id, 'actor_id' => $actor->id, 'correlation_id' => $correlation,
            'event' => $event, 'from_status' => $from, 'to_status' => $meeting->status,
            'schedule_version' => $meeting->schedule_version, 'action_item_id' => $actionItemId,
            'created_at' => now(),
        ]);
    }

    public static function recordAutomaticMiss(Consultation $meeting, string $correlation): void
    {
        static::create([
            'consultation_id' => $meeting->id, 'actor_id' => null, 'actor_type' => 'automation',
            'correlation_id' => $correlation, 'event' => 'elapsed_meeting_detected',
            'from_status' => 'confirmed', 'to_status' => 'missed',
            'schedule_version' => $meeting->schedule_version, 'created_at' => now(),
        ]);
    }
}
