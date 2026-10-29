<?php

namespace App\Services;

use App\Models\AutomationItem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AutomationDelivery
{
    public function __construct(private AutomationEligibility $eligibility) {}

    public function process(int $id): array
    {
        if (config('consultation.notification_url') !== 'http://127.0.0.1:8090/notify' || strlen(config('consultation.notification_key') ?? '') < 32) {
            throw new RuntimeException('notification_configuration_invalid');
        }
        $claim = DB::transaction(function () use ($id) {
            $work = AutomationItem::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($work->status === 'claimed' && $work->claimed_until?->isPast()) {
                DB::table('automation_attempts')->where('claim_token', $work->claim_token)->where('outcome', 'started')
                    ->update(['outcome' => 'interrupted', 'code' => 'lease_expired', 'finished_at' => now()]);
                $work->status = $work->attempts >= 3 ? 'needs_review' : 'retry_wait';
                $work->failure_code = 'lease_expired';
                $work->available_at = now();
                $work->claim_token = null;
                $work->claimed_until = null;
                $work->save();
            }
            if (! in_array($work->status, ['pending', 'retry_wait'], true) || $work->available_at->isFuture()) {
                return ['work' => $work, 'token' => null];
            }
            $check = $this->eligibility->check($work);
            if ($check['state'] !== 'send' || $work->attempts >= 3) {
                $work->status = $work->attempts >= 3 ? 'needs_review' : $check['state'];
                $work->failure_code = $work->attempts >= 3 ? 'attempt_limit' : $check['code'];
                if ($work->status === 'cancelled' && $work->total_attempts > 0) {
                    $work->status = 'needs_review';
                    $work->failure_code = 'source_changed_after_attempt';
                }
                $work->save();

                return ['work' => $work, 'token' => null];
            }
            $token = (string) Str::uuid();
            $work->forceFill([
                'status' => 'claimed', 'attempts' => $work->attempts + 1, 'total_attempts' => $work->total_attempts + 1,
                'claim_token' => $token, 'claimed_until' => now()->addSeconds(30),
            ])->save();
            DB::table('automation_attempts')->insert([
                'automation_item_id' => $work->id, 'cycle' => $work->cycle, 'number' => $work->attempts,
                'claim_token' => $token, 'started_at' => now(), 'outcome' => 'started',
            ]);

            return ['work' => $work, 'token' => $token];
        });
        $work = $claim['work'];
        $token = $claim['token'];
        if ($token === null) {
            return $this->report($work, 'no_call');
        }

        $start = microtime(true);
        $httpStatus = null;
        $receipt = null;
        $transient = false;
        // One network call per reserved attempt; never use nested HTTP retries.
        try {
            $response = Http::withToken(config('consultation.notification_key'))->acceptJson()->withoutRedirecting()
                ->connectTimeout(2)->timeout(5)->post(config('consultation.notification_url'), [
                    'work_key' => $work->deduplication_key, 'correlation_id' => $work->correlation_id,
                    'kind' => $work->kind, 'recipient_id' => $work->recipient_id,
                    'scheduled_for' => $work->scheduled_for->format('Y-m-d\TH:i:s\Z'), 'data' => $work->payload,
                ]);
            $httpStatus = $response->status();
            $candidate = $response->json('receipt');
            if ($response->successful() && is_string($candidate) && preg_match('/^rcpt_[a-f0-9]{32}$/', $candidate)
                && $response->json('work_key') === $work->deduplication_key) {
                $receipt = $candidate;
                $code = 'accepted';
            } else {
                $transient = $httpStatus === 429 || $httpStatus >= 500;
                $code = $response->successful() ? 'invalid_receipt' : 'http_'.$httpStatus;
            }
        } catch (ConnectionException) {
            $transient = true;
            $code = 'connection_or_timeout';
        }
        $duration = (int) round((microtime(true) - $start) * 1000);
        $resultCode = $code;
        $finished = DB::transaction(function () use ($id, $token, $receipt, $code, $transient, $httpStatus, $duration, &$resultCode) {
            $current = AutomationItem::whereKey($id)->lockForUpdate()->firstOrFail();
            // A paused/late worker cannot overwrite a newer lease holder's result.
            if ($current->status !== 'claimed' || $current->claim_token !== $token) {
                $resultCode = 'late_result_ignored';

                return $current;
            }
            $state = $receipt ? 'delivered' : ($transient && $current->attempts < 3 ? 'retry_wait' : 'needs_review');
            $current->forceFill([
                'status' => $state, 'failure_code' => $receipt ? null : $code, 'receipt' => $receipt,
                'claimed_until' => null, 'claim_token' => null,
                'available_at' => $state === 'retry_wait' ? now()->addSeconds(30 * (2 ** ($current->attempts - 1)) + random_int(0, 5)) : now(),
            ])->save();
            DB::table('automation_attempts')->where('claim_token', $token)->update([
                'outcome' => $state, 'code' => $code, 'http_status' => $httpStatus,
                'receipt' => $receipt, 'duration_ms' => $duration, 'finished_at' => now(),
            ]);

            return $current;
        });

        return $this->report($finished, $resultCode);
    }

    private function report(AutomationItem $work, string $code): array
    {
        $result = ['work_id' => $work->id, 'correlation_id' => $work->correlation_id, 'status' => $work->status,
            'cycle' => $work->cycle, 'attempts' => $work->attempts, 'total_attempts' => $work->total_attempts,
            'code' => $code, 'receipt' => $work->receipt, 'log_status' => 'recorded'];
        try {
            Log::channel('automation')->info('delivery_state', $result + ['environment' => app()->environment()]);
        } catch (Throwable) {
            $result['log_status'] = 'unavailable';
        }

        return $result;
    }
}
