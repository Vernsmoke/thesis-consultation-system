<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Consultation;
use App\Models\ConsultationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    public function recordOutcome(Request $request, Consultation $consultation)
    {
        Gate::authorize('recordOutcome', $consultation);
        $data = $request->validate([
            'status' => ['required', Rule::in(['completed', 'missed'])],
            'meeting_notes' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $correlation = (string) Str::uuid();
        $meeting = DB::transaction(function () use ($request, $consultation, $data, $correlation) {
            $meeting = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('recordOutcome', $meeting);
            abort_unless($meeting->status === 'confirmed', 409, 'Only a confirmed meeting can receive an outcome.');
            abort_if($meeting->ends_at->isFuture(), 409, 'Record the outcome after the scheduled meeting has ended.');
            $meeting->update($data);
            ConsultationEvent::record($meeting, $request->user(), 'meeting_'.$data['status'], 'confirmed', $correlation);

            return $meeting;
        });

        return response()->json(['data' => $meeting, 'correlation_id' => $correlation]);
    }

    public function addActionItem(Request $request, Consultation $consultation)
    {
        Gate::authorize('addActionItem', $consultation);
        $data = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:500'],
            'due_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z', 'after:now'],
            'assigned_to_id' => ['prohibited'], 'completed_at' => ['prohibited'], 'deadline_version' => ['prohibited'],
        ]);
        $correlation = (string) Str::uuid();
        $item = DB::transaction(function () use ($request, $consultation, $data, $correlation) {
            $meeting = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('addActionItem', $meeting);
            abort_unless($meeting->status === 'completed', 409, 'Action items require a completed meeting.');
            $item = ActionItem::create([
                'consultation_id' => $meeting->id, 'assigned_to_id' => $meeting->student_id,
                'description' => $data['description'], 'due_at' => $data['due_at'], 'deadline_version' => 1, 'completed_at' => null,
            ]);
            ConsultationEvent::record($meeting, $request->user(), 'action_item_assigned', $meeting->status, $correlation, $item->id);

            return $item;
        });

        return response()->json(['data' => $item, 'correlation_id' => $correlation], 201);
    }

    public function completeActionItem(Request $request, ActionItem $actionItem)
    {
        Gate::authorize('complete', $actionItem);
        $correlation = (string) Str::uuid();
        [$item, $changed] = DB::transaction(function () use ($request, $actionItem, $correlation) {
            // Keep the lock order consultation -> action item for all workflow writes.
            $meeting = Consultation::whereKey($actionItem->consultation_id)->lockForUpdate()->firstOrFail();
            $item = ActionItem::whereKey($actionItem->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('complete', $item);
            abort_unless($meeting->status === 'completed', 409, 'Action completion requires a completed meeting.');
            if ($item->completed_at !== null) {
                return [$item, false];
            }
            $item->update(['completed_at' => now()]);
            ConsultationEvent::record($meeting, $request->user(), 'action_item_completed', $meeting->status, $correlation, $item->id);

            return [$item, true];
        });

        return response()->json(['data' => $item, 'changed' => $changed, 'correlation_id' => $changed ? $correlation : null]);
    }

    public function followUp(Request $request, Consultation $consultation)
    {
        Gate::authorize('followUp', $consultation);
        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:2000'], 'author_id' => ['prohibited'],
        ]);
        $correlation = (string) Str::uuid();
        $note = DB::transaction(function () use ($request, $consultation, $data, $correlation) {
            $meeting = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('followUp', $meeting);
            abort_unless(in_array($meeting->status, ['completed', 'missed'], true), 409, 'Follow-up requires a completed or missed meeting.');
            $note = $meeting->followUps()->create(['author_id' => $request->user()->id, 'message' => $data['message']]);
            ConsultationEvent::record($meeting, $request->user(), 'follow_up_added', $meeting->status, $correlation);

            return $note;
        });

        return response()->json(['data' => $note->load('author:id,name,role'), 'correlation_id' => $correlation], 201);
    }
}
