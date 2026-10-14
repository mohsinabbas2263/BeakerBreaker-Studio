<?php

namespace App\Http\Controllers\Api;

use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\ExperimentStep;
use App\Services\ExperimentAccessService;
use App\Services\GuidanceService;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    public function __construct(
        protected ExperimentAccessService $access,
        protected GuidanceService $guidance,
        protected RewardService $rewards,
    ) {}

    /** FR-03.1 the library, FR-03.2 filtering by difficulty and topic. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'topic' => ['nullable', 'string', 'max:60'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'ar_only' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $experiments = Experiment::query()
            ->where('is_published', true)
            ->when($filters['topic'] ?? null, fn ($q, $t) => $q->where('topic', $t))
            ->when($filters['difficulty'] ?? null, fn ($q, $d) => $q->where('difficulty', $d))
            ->when($filters['ar_only'] ?? false, fn ($q) => $q->where('ar_available', true))
            ->withCount('steps')
            ->orderBy('grade_level')
            ->orderBy('base_points')
            ->get();

        $completed = $user->experimentSessions()
            ->whereNotNull('completed_at')
            ->pluck('experiment_id')
            ->unique();

        return response()->json([
            'topics' => Experiment::where('is_published', true)->distinct()->pluck('topic'),
            'experiments' => $experiments->map(function (Experiment $e) use ($user, $completed) {
                $check = $this->access->check($user, $e);

                return [
                    'id' => $e->id,
                    'slug' => $e->slug,
                    'title' => $e->title,
                    'topic' => $e->topic,
                    'difficulty' => $e->difficulty,
                    'emoji' => $e->emoji,
                    'card_gradient' => $e->card_gradient,
                    'base_points' => $e->base_points,
                    'estimated_minutes' => $e->estimated_minutes,
                    'step_count' => $e->steps_count,
                    'ar_available' => $e->ar_available,
                    'completed' => $completed->contains($e->id),
                    // FR-03.3: locked experiments carry the reason with them.
                    'locked' => ! $check['allowed'],
                    'lock_reason' => $check['reason'],
                    'lock_code' => $check['code'],
                ];
            }),
        ]);
    }

    /** FR-03.6: the equation and the explanatory note travel with the detail. */
    public function show(Request $request, Experiment $experiment): JsonResponse
    {
        $user = $request->user();
        $check = $this->access->check($user, $experiment);

        $session = $user->experimentSessions()
            ->where('experiment_id', $experiment->id)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        return response()->json([
            'experiment' => [
                'id' => $experiment->id,
                'slug' => $experiment->slug,
                'title' => $experiment->title,
                'topic' => $experiment->topic,
                'difficulty' => $experiment->difficulty,
                'emoji' => $experiment->emoji,
                'card_gradient' => $experiment->card_gradient,
                'chemical_equation' => $experiment->chemical_equation,
                'explanatory_note' => $experiment->explanatory_note,
                'base_points' => $experiment->base_points,
                'estimated_minutes' => $experiment->estimated_minutes,
                'ar_available' => $experiment->ar_available,
                'required_safety_tier' => $experiment->requiredSafetyTier(),
            ],
            'access' => $check,
            // FR-03.4: steps in their declared order.
            'steps' => $check['allowed'] ? $experiment->steps->map(fn (ExperimentStep $s) => [
                'id' => $s->id,
                'step_order' => $s->step_order,
                'title' => $s->title,
                'instruction_text' => $s->instruction_text,
                'item_emoji' => $s->item_emoji,
                'action_label' => $s->action_label,
            ]) : [],
            // FR-03.5: an unfinished attempt comes back with its progress.
            'resume' => $session ? [
                'session_id' => $session->id,
                'steps_completed' => $session->steps_completed,
                'completed_step_ids' => $session->completed_step_ids ?? [],
                'presentation_mode' => $session->presentation_mode,
            ] : null,
        ]);
    }

    /** FR-03.8: repeating a completed experiment opens a new session. */
    public function start(Request $request, Experiment $experiment): JsonResponse
    {
        $validated = $request->validate([
            'presentation_mode' => ['required', 'in:standard,ar'],
        ]);

        $user = $request->user();
        $check = $this->access->check($user, $experiment);

        if (! $check['allowed']) {
            return response()->json(['message' => $check['reason'], 'code' => $check['code']], 403);
        }

        // FR-05.1: AR is offered only where it exists.
        if ($validated['presentation_mode'] === 'ar' && ! $experiment->ar_available) {
            return response()->json([
                'message' => 'This experiment has no augmented reality presentation.',
                'code' => 'ar_unavailable',
            ], 422);
        }

        $session = $user->experimentSessions()
            ->where('experiment_id', $experiment->id)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        $session ??= ExperimentSession::create([
            'user_id' => $user->id,
            'experiment_id' => $experiment->id,
            'presentation_mode' => $validated['presentation_mode'],
            'started_at' => now(),
            'steps_completed' => 0,
            'completed_step_ids' => [],
        ]);

        $this->rewards->recordActivity($user);

        return response()->json(['session' => $session], $session->wasRecentlyCreated ? 201 : 200);
    }

    /** FR-03.5: each completion is written at the time it happens. */
    public function completeStep(Request $request, ExperimentSession $session): JsonResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'step_id' => ['required', 'integer', 'exists:experiment_steps,id'],
        ]);

        if ($session->isComplete()) {
            return response()->json(['message' => 'This session is already finished.'], 422);
        }

        $step = ExperimentStep::findOrFail($validated['step_id']);

        abort_unless($step->experiment_id === $session->experiment_id, 422);

        $done = collect($session->completed_step_ids ?? []);

        // FR-03.4: a step may not be completed before the one in front of it.
        $expectedOrder = $done->count() + 1;
        if ($step->step_order !== $expectedOrder) {
            return response()->json([
                'message' => "Complete step {$expectedOrder} first.",
                'code' => 'out_of_order',
            ], 422);
        }

        $done->push($step->id);
        $total = $session->experiment->steps()->count();
        $allDone = $done->count() >= $total;

        $session->update([
            'completed_step_ids' => $done->unique()->values()->all(),
            'steps_completed' => $done->unique()->count(),
            'completed_at' => $allDone ? now() : null,
        ]);

        return response()->json([
            'session' => $session->fresh(),
            'steps_completed' => $session->steps_completed,
            'step_count' => $total,
            // FR-04.1: the quiz opens only once every step is done.
            'quiz_unlocked' => $allDone,
        ]);
    }

    /**
     * FR-03.7 and NFR-06: guidance for the current step. The response always
     * carries text, and 'source' says whether it came from the AI service or
     * from the pre-authored line stored with the step.
     */
    public function guidance(Request $request, ExperimentStep $step): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['nullable', 'string', 'max:300'],
        ]);

        $check = $this->access->check($request->user(), $step->experiment);

        if (! $check['allowed']) {
            return response()->json(['message' => $check['reason'], 'code' => $check['code']], 403);
        }

        return response()->json(
            $this->guidance->forStep($step, $validated['question'] ?? null)
        );
    }
}
