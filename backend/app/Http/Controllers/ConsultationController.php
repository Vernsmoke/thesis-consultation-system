<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConsultationController extends Controller
{
    private const PARTICIPANTS = ['student:id,name,role', 'adviser:id,name,role', 'panelMembers:id,name,role'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Consultation::class);

        return Consultation::visibleTo($request->user())->with(self::PARTICIPANTS)->latest('id')->paginate(20);
    }

    public function advisers()
    {
        Gate::authorize('create', Consultation::class);

        return response()->json(['data' => User::where('role', 'adviser')->orderBy('name')->limit(100)->get(['id', 'name'])]);
    }

    public function show(Consultation $consultation)
    {
        Gate::authorize('view', $consultation);

        return response()->json(['data' => $consultation->load(self::PARTICIPANTS)->load('actionItems')]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Consultation::class);
        $data = $request->validate([
            'adviser_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'adviser')],
            'agenda' => ['required', 'string', 'min:10', 'max:2000'],
            'student_id' => ['prohibited'], 'status' => ['prohibited'], 'role' => ['prohibited'],
            'starts_at' => ['prohibited'], 'ends_at' => ['prohibited'], 'schedule_version' => ['prohibited'],
        ]);
        $id = (string) Str::uuid();
        $consultation = DB::transaction(function () use ($data, $request, $id) {
            $consultation = Consultation::create([
                'reference' => (string) Str::uuid(),
                'student_id' => $request->user()->id,
                'adviser_id' => $data['adviser_id'], 'agenda' => $data['agenda'],
                'status' => 'requested', 'schedule_version' => 0,
            ]);
            $this->recordEvent($consultation, $request->user(), 'consultation_requested', null, $id);

            return $consultation;
        });

        return response()->json(['data' => $consultation->load(self::PARTICIPANTS), 'correlation_id' => $id], 201);
    }

    public function confirmSchedule(Request $request, Consultation $consultation)
    {
        Gate::authorize('confirmSchedule', $consultation);
        $data = $request->validate([
            // Explicit UTC format avoids ambiguity between Manila and server time.
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z', 'after:now'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z', 'after:starts_at'],
            'student_id' => ['prohibited'], 'adviser_id' => ['prohibited'], 'status' => ['prohibited'],
        ]);
        $id = (string) Str::uuid();
        $meeting = DB::transaction(function () use ($request, $consultation, $data, $id) {
            // Serializing confirmations for one adviser prevents concurrent overlap.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $meeting = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('confirmSchedule', $meeting);
            abort_unless($meeting->status === 'requested', 409, 'Only a requested consultation can be confirmed.');
            $overlap = Consultation::where('adviser_id', $meeting->adviser_id)
                ->where('status', 'confirmed')->where('starts_at', '<', Carbon::parse($data['ends_at']))
                ->where('ends_at', '>', Carbon::parse($data['starts_at']))->exists();
            abort_if($overlap, 409, 'That adviser already has a confirmed consultation during this time.');
            $meeting->update([
                'status' => 'confirmed', 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
                'schedule_version' => $meeting->schedule_version + 1,
            ]);
            $this->recordEvent($meeting, $request->user(), 'schedule_confirmed', 'requested', $id);

            return $meeting;
        });

        return response()->json(['data' => $meeting->load(self::PARTICIPANTS), 'correlation_id' => $id]);
    }

    private function recordEvent(Consultation $meeting, User $actor, string $event, ?string $from, string $correlation): void
    {
        DB::table('consultation_events')->insert([
            'consultation_id' => $meeting->id, 'actor_id' => $actor->id, 'correlation_id' => $correlation,
            'event' => $event, 'from_status' => $from, 'to_status' => $meeting->status,
            'schedule_version' => $meeting->schedule_version, 'created_at' => now(),
        ]);
    }
}
