<?php

namespace App\Http\Controllers;

use App\Models\AutomationItem;
use App\Services\AutomationEligibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        return $this->visible($request)->latest('id')->paginate(20);
    }

    public function notifications(Request $request)
    {
        // Here delivered means accepted by the controlled receiver, not email/read.
        return $this->visible($request)->where('recipient_id', $request->user()->id)->where('status', 'delivered')->latest('id')->paginate(20);
    }

    private function visible(Request $request): Builder
    {
        Gate::authorize('viewAny', AutomationItem::class);
        $user = $request->user();
        $query = AutomationItem::query();
        if ($user->role !== 'coordinator') {
            $query->where('recipient_id', $user->id)->where(function (Builder $allowed) use ($user) {
                $allowed->whereHas('consultation', fn (Builder $meeting) => $meeting->visibleTo($user));
                if ($user->role === 'adviser') {
                    $allowed->orWhere(function (Builder $summary) use ($user) {
                        $summary->where('kind', 'progress_summary')->where('payload->adviser_id', $user->id);
                    });
                }
            });
        }

        return $query;
    }

    public function show(Request $request, AutomationItem $automationItem)
    {
        Gate::authorize('view', $automationItem);
        $result = ['data' => $automationItem, 'attempts' => DB::table('automation_attempts')->where('automation_item_id', $automationItem->id)->orderBy('id')->limit(20)
            ->get(['id', 'cycle', 'number', 'started_at', 'finished_at', 'outcome', 'code', 'http_status', 'duration_ms', 'receipt'])];
        if ($request->user()->role === 'coordinator') {
            $result['recoveries'] = DB::table('automation_recoveries')->where('automation_item_id', $automationItem->id)->orderBy('id')->limit(3)->get();
        }

        return response()->json($result);
    }

    public function recover(Request $request, AutomationItem $automationItem, AutomationEligibility $eligibility)
    {
        Gate::authorize('recover', $automationItem);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $work = DB::transaction(function () use ($request, $automationItem, $data, $eligibility) {
            $work = AutomationItem::whereKey($automationItem->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('recover', $work);
            abort_unless($work->status === 'needs_review' && $work->receipt === null, 409, 'Only unresolved review work may be recovered.');
            abort_if($work->cycle >= 3, 409, 'Recovery limit reached. Further automatic processing requires maintainer review.');
            abort_unless($eligibility->check($work)['state'] === 'send', 409, 'Correct or review the source record before recovery.');
            $work->forceFill(['cycle' => $work->cycle + 1, 'attempts' => 0, 'status' => 'pending', 'failure_code' => null,
                'available_at' => now(), 'claim_token' => null, 'claimed_until' => null])->save();
            DB::table('automation_recoveries')->insert(['automation_item_id' => $work->id, 'actor_id' => $request->user()->id,
                'cycle' => $work->cycle, 'reason' => $data['reason'], 'created_at' => now()]);

            return $work;
        });
        try {
            Log::channel('automation')->info('recovery_authorized', ['work_id' => $work->id, 'correlation_id' => $work->correlation_id,
                'cycle' => $work->cycle, 'actor_id' => $request->user()->id, 'environment' => app()->environment()]);
        } catch (Throwable) {
            // Recovery remains durable even when telemetry is unavailable.
        }

        return response()->json(['data' => $work, 'message' => 'Recovery queued; prior history retained.']);
    }
}
