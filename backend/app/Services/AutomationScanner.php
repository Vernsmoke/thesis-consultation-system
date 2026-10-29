<?php

namespace App\Services;

use App\Models\ActionItem;
use App\Models\AutomationItem;
use App\Models\Consultation;
use App\Models\ConsultationEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AutomationScanner
{
    public function scan(): array
    {
        $limit = config('consultation.scan_limit');
        $hours = config('consultation.reminder_hours');
        if ($limit < 1 || $limit > 1000 || $hours < 1 || $hours > 72) {
            throw new RuntimeException('invalid_scan_configuration');
        }
        $runId = (string) Str::uuid();
        // No HTTP call occurs in this transaction. A failed category rolls back all.
        [$created, $missed] = DB::transaction(function () use ($limit, $hours) {
            // Serializes scanners and names a real operational review owner.
            $owner = User::query()->where('role', 'coordinator')->orderBy('id')->lockForUpdate()->first();
            if (! $owner) {
                throw new RuntimeException('missing_review_owner');
            }
            $created = [];
            $missed = 0;
            $reminders = $this->bounded(Consultation::query()->where('status', 'confirmed')
                ->where('starts_at', '>', now())->where('starts_at', '<=', now()->addHours($hours))->lockForUpdate(), $limit);
            foreach ($reminders as $meeting) {
                if (! $this->validParticipants($meeting)) {
                    $this->review($meeting, $owner, 'reminder', $created);

                    continue;
                }
                foreach ([$meeting->student_id, $meeting->adviser_id] as $recipient) {
                    $this->enqueue('reminder', 'meeting:'.$meeting->id.':'.$meeting->schedule_version, $recipient, $owner,
                        $meeting->starts_at, ['consultation_id' => $meeting->id, 'schedule_version' => $meeting->schedule_version],
                        $created, $meeting, null, $meeting->schedule_version);
                }
            }
            $elapsed = $this->bounded(Consultation::query()->where('status', 'confirmed')->where('ends_at', '<=', now())->lockForUpdate(), $limit);
            foreach ($elapsed as $meeting) {
                if (! $this->validParticipants($meeting)) {
                    $this->review($meeting, $owner, 'missed_session', $created);

                    continue;
                }
                $meeting->update(['status' => 'missed']);
                $transitionId = (string) Str::uuid();
                ConsultationEvent::recordAutomaticMiss($meeting, $transitionId);
                $missed++;
                foreach (array_unique([$meeting->student_id, $meeting->adviser_id, $owner->id]) as $recipient) {
                    $this->enqueue('missed_session', 'meeting:'.$meeting->id.':'.$meeting->schedule_version, $recipient, $owner,
                        $meeting->ends_at, [
                            'consultation_id' => $meeting->id, 'requires_human_review' => true,
                            'basis' => 'elapsed_without_recorded_outcome', 'transition_correlation_id' => $transitionId,
                        ],
                        $created, $meeting, null, $meeting->schedule_version);
                }
            }
            $overdue = $this->bounded(ActionItem::query()->whereNull('completed_at')->where('due_at', '<=', now())->lockForUpdate(), $limit);
            foreach ($overdue as $item) {
                $meeting = $item->consultation;
                if (! $this->validParticipants($meeting) || $meeting->status !== 'completed' || $item->assigned_to_id !== $meeting->student_id) {
                    $this->review($meeting, $owner, 'action_deadline', $created, $item);

                    continue;
                }
                foreach ([$meeting->student_id, $meeting->adviser_id] as $recipient) {
                    $this->enqueue('action_deadline', 'action:'.$item->id.':'.$item->deadline_version, $recipient, $owner,
                        $item->due_at, ['action_item_id' => $item->id, 'deadline_version' => $item->deadline_version],
                        $created, $meeting, $item, $item->deadline_version);
                }
            }
            $advisers = $this->bounded(User::query()->where('role', 'adviser')->whereIn('id', Consultation::query()->select('adviser_id')->distinct()), $limit);
            $day = now()->timezone('Asia/Manila')->format('Y-m-d');
            $dayStart = Carbon::parse($day, 'Asia/Manila')->utc();
            foreach ($advisers as $adviser) {
                $counts = [];
                foreach (['requested', 'confirmed', 'completed', 'missed'] as $status) {
                    $counts[$status] = Consultation::where('adviser_id', $adviser->id)->where('status', $status)->count();
                }
                $counts['open_overdue_actions'] = ActionItem::query()->whereHas('consultation', fn (Builder $query) => $query->where('adviser_id', $adviser->id))
                    ->whereNull('completed_at')->where('due_at', '<=', now())->count();
                foreach ([$adviser->id, $owner->id] as $recipient) {
                    $this->enqueue('progress_summary', 'adviser:'.$adviser->id.':'.$day, $recipient, $owner, $dayStart,
                        ['adviser_id' => $adviser->id, 'local_date' => $day, 'counts' => $counts], $created);
                }
            }

            return [$created, $missed];
        });
        $result = ['run_id' => $runId, 'created' => count($created), 'missed_detected' => $missed, 'delivery' => 'separate_worker', 'log_status' => 'recorded'];
        try {
            foreach ($created as $item) {
                Log::channel('automation')->info('work_queued', [
                    'run_id' => $runId, 'correlation_id' => $item->correlation_id, 'work_id' => $item->id,
                    'kind' => $item->kind, 'status' => $item->status, 'failure_code' => $item->failure_code,
                    'transition_correlation_id' => $item->payload['transition_correlation_id'] ?? null,
                    'environment' => app()->environment(), 'database' => DB::connection()->getDatabaseName(),
                ]);
            }
            Log::channel('automation')->info('scan_completed', $result + ['environment' => app()->environment()]);
        } catch (Throwable) {
            // The database transaction is already committed: never claim rollback here.
            $result['log_status'] = 'unavailable';
        }

        return $result;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>
     */
    private function bounded(Builder $query, int $limit): Collection
    {
        $rows = $query->orderBy('id')->limit($limit + 1)->get();
        if ($rows->count() > $limit) {
            throw new RuntimeException('scan_capacity_exceeded');
        }

        return $rows;
    }

    private function validParticipants(Consultation $meeting): bool
    {
        return $meeting->student?->role === 'student' && $meeting->adviser?->role === 'adviser';
    }

    private function review(Consultation $meeting, User $owner, string $kind, array &$created, ?ActionItem $item = null): void
    {
        $this->enqueue($kind, 'invalid:'.($item ? 'action:'.$item->id.':'.$item->deadline_version : 'meeting:'.$meeting->id.':'.$meeting->schedule_version),
            $owner->id, $owner, now(), ['consultation_id' => $meeting->id, 'requires_human_review' => true],
            $created, $meeting, $item, $item?->deadline_version ?? $meeting->schedule_version, 'invalid_source_record');
    }

    private function enqueue(string $kind, string $identity, int $recipient, User $owner, CarbonInterface $scheduledFor, array $payload,
        array &$created, ?Consultation $meeting = null, ?ActionItem $item = null, int $version = 0, ?string $failure = null): void
    {
        $work = AutomationItem::firstOrCreate(['deduplication_key' => hash('sha256', $kind.':'.$identity.':recipient:'.$recipient)], [
            'correlation_id' => (string) Str::uuid(), 'kind' => $kind, 'consultation_id' => $meeting?->id,
            'action_item_id' => $item?->id, 'recipient_id' => $recipient, 'review_owner_id' => $owner->id,
            'source_version' => $version, 'scheduled_for' => $scheduledFor, 'payload' => $payload,
            'status' => $failure ? 'needs_review' : 'pending', 'failure_code' => $failure, 'available_at' => now(),
        ]);
        if ($work->wasRecentlyCreated) {
            $created[] = $work;
        }
    }
}
