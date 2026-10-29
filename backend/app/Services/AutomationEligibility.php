<?php

namespace App\Services;

use App\Models\ActionItem;
use App\Models\AutomationItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutomationEligibility
{
    public function check(AutomationItem $work): array
    {
        $bad = ['state' => 'needs_review', 'code' => 'invalid_work_record'];
        $stale = ['state' => 'cancelled', 'code' => 'source_obsolete'];
        $owner = User::find($work->review_owner_id);
        if ($owner?->role !== 'coordinator' || ! Str::isUuid($work->correlation_id) || ! is_array($work->payload)) {
            return $bad;
        }
        $payload = $work->payload;
        if ($work->kind === 'progress_summary') {
            if (! is_int($payload['adviser_id'] ?? null)) {
                return $bad;
            }
            $adviser = User::find($payload['adviser_id'] ?? 0);
            $date = $payload['local_date'] ?? '';
            if ($adviser?->role !== 'adviser' || ! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || array_diff(array_keys($payload), ['adviser_id', 'local_date', 'counts'])) {
                return $bad;
            }
            if (! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
                return $bad;
            }
            $day = Carbon::parse($date, 'Asia/Manila');
            $counts = $payload['counts'] ?? [];
            if (! is_array($counts) || $day->format('Y-m-d') !== $date || $day->isFuture() || ! $work->scheduled_for->equalTo($day->utc()) || $work->source_version !== 0
                || array_keys($counts) !== ['requested', 'confirmed', 'completed', 'missed', 'open_overdue_actions']) {
                return $bad;
            }
            foreach ($counts as $count) {
                if (! is_int($count) || $count < 0 || $count > 1000000) {
                    return $bad;
                }
            }
            $identity = 'adviser:'.$adviser->id.':'.$date;
            $recipients = [$adviser->id, $owner->id];
        } else {
            $meeting = $work->consultation;
            if (! $meeting || $meeting->student?->role !== 'student' || $meeting->adviser?->role !== 'adviser') {
                return $bad;
            }
            $recipients = [$meeting->student_id, $meeting->adviser_id];
            if ($work->kind === 'action_deadline') {
                $item = ActionItem::find($work->action_item_id);
                if (! $item || $item->consultation_id !== $meeting->id || $item->assigned_to_id !== $meeting->student_id
                    || array_diff(array_keys($payload), ['action_item_id', 'deadline_version'])
                    || ($payload['action_item_id'] ?? null) !== $item->id || ($payload['deadline_version'] ?? null) !== $work->source_version) {
                    return $bad;
                }
                if ($meeting->status !== 'completed' || $item->completed_at || $item->due_at->isFuture()
                    || $item->deadline_version !== $work->source_version || ! $work->scheduled_for->equalTo($item->due_at)) {
                    return $stale;
                }
                $identity = 'action:'.$item->id.':'.$item->deadline_version;
            } elseif ($work->kind === 'reminder') {
                if (array_diff(array_keys($payload), ['consultation_id', 'schedule_version'])
                    || ($payload['consultation_id'] ?? null) !== $meeting->id || ($payload['schedule_version'] ?? null) !== $work->source_version) {
                    return $bad;
                }
                if ($meeting->status !== 'confirmed' || $meeting->schedule_version !== $work->source_version
                    || ! $work->scheduled_for->equalTo($meeting->starts_at) || ! $meeting->starts_at->isFuture()) {
                    return $stale;
                }
                $identity = 'meeting:'.$meeting->id.':'.$meeting->schedule_version;
            } elseif ($work->kind === 'missed_session') {
                if (array_diff(array_keys($payload), ['consultation_id', 'requires_human_review', 'basis', 'transition_correlation_id'])
                    || ($payload['consultation_id'] ?? null) !== $meeting->id || ($payload['requires_human_review'] ?? null) !== true
                    || ($payload['basis'] ?? null) !== 'elapsed_without_recorded_outcome'
                    || ! Str::isUuid($payload['transition_correlation_id'] ?? '')) {
                    return $bad;
                }
                if (! DB::table('consultation_events')->where('consultation_id', $meeting->id)
                    ->where('correlation_id', $payload['transition_correlation_id'])->where('actor_type', 'automation')
                    ->where('event', 'elapsed_meeting_detected')->exists()) {
                    return $bad;
                }
                if ($meeting->status !== 'missed' || $meeting->schedule_version !== $work->source_version || ! $work->scheduled_for->equalTo($meeting->ends_at)) {
                    return $stale;
                }
                $identity = 'meeting:'.$meeting->id.':'.$meeting->schedule_version;
                $recipients[] = $owner->id;
            } else {
                return $bad;
            }
        }
        if (! in_array($work->recipient_id, $recipients, true)
            || ! hash_equals(hash('sha256', $work->kind.':'.$identity.':recipient:'.$work->recipient_id), $work->deduplication_key)) {
            return $bad;
        }

        return ['state' => 'send', 'code' => null];
    }
}
