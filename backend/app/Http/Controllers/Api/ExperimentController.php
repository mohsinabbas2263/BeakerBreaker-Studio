<?php

namespace App\Http\Controllers\Api;

use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\ExperimentStep;
use App\Models\StepAction;
use App\Services\ExperimentAccessService;
use App\Services\GuidanceService;
use App\Services\AchievementService;
use App\Services\RewardService;
use App\Support\Badges;
use App\Services\StepJudge;
use App\Services\TaskProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    public function __construct(
        protected ExperimentAccessService $access,
        protected GuidanceService $guidance,
        protected RewardService $rewards,
        protected TaskProgressService $tasks,
        protected StepJudge $judge,
        protected AchievementService $achievements,
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

        // Licensing: a student sees only their own class's grade, if their school is
        // licensed for it, and only their school's board (or experiments in both boards).
        $visible = fn () => $this->access->restrictToLicence(Experiment::query()->where('is_published', true), $user);

        $experiments = $visible()
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
            'topics' => $visible()->distinct()->pluck('topic'),
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

        // Licensing: outside the student's class or school licence the experiment does
        // not exist for them, so its title and steps are not shown either.
        abort_if($this->access->licence($user, $experiment)['code'] === 'not_licensed', 404);

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
                // docs/experiment-engine.md: the kind, its config with the answers
                // removed (FR-02.5), and the avatar's opening line.
                'kind' => $s->kind ?? 'confirm',
                'config' => $s->publicConfig(),
                'intro' => $s->messages['intro'] ?? null,
            ]) : [],
            // FR-03.5: an unfinished attempt comes back with its progress.
            'resume' => $session ? [
                'session_id' => $session->id,
                'steps_completed' => $session->steps_completed,
                'completed_step_ids' => $session->completed_step_ids ?? [],
                'presentation_mode' => $session->presentation_mode,
                // NFR-05: progress inside the current step, so a refresh resumes mid-step.
                'step_state' => $this->publicStepState($session),
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

        // FR-09.5: a task for this experiment is now in progress.
        $this->tasks->experimentStarted($user, $experiment->id);

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

        if ($refusal = $this->outOfOrder($session, $step)) {
            return $refusal;
        }

        // FR-02.5: a game step is judged on the server through its actions; a client
        // cannot skip it by declaring it done here.
        if ($step->isGame()) {
            return response()->json([
                'message' => 'This step is completed through its actions.',
                'code' => 'use_actions',
            ], 422);
        }

        return response()->json($this->advance($request, $session, $step));
    }

    /**
     * docs/experiment-engine.md: one move inside a game step. The client sends what the
     * student did; the server judges it (FR-02.5), records it in step_actions for the
     * readiness report (FR-08.1), and answers with the avatar's line and mood.
     */
    public function act(Request $request, ExperimentSession $session, ExperimentStep $step): JsonResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        if ($session->isComplete()) {
            return response()->json(['message' => 'This session is already finished.'], 422);
        }

        abort_unless($step->experiment_id === $session->experiment_id, 422);

        if ($refusal = $this->outOfOrder($session, $step)) {
            return $refusal;
        }

        $request->validate(array_merge([
            'action' => ['present', 'array'],
            'hinted' => ['sometimes', 'boolean'],
        ], self::ACTION_RULES[$step->kind ?? 'confirm'] ?? []));

        $action = $request->input('action', []);
        $previous = ($session->step_state['step_id'] ?? null) === $step->id ? $session->step_state : [];

        $verdict = $this->judge->judge($step, $action, $previous, fn (int $order) => $this->readingFrom($session, $order));

        StepAction::create([
            'session_id' => $session->id,
            'step_id' => $step->id,
            'user_id' => $request->user()->id,
            'kind' => $step->kind ?? 'confirm',
            'action' => $action,
            'outcome' => $verdict['outcome'],
            'category' => $verdict['category'],
            'mistake_code' => $verdict['mistake_code'],
            'hinted' => $request->boolean('hinted'),
        ]);

        if ($verdict['step_complete']) {
            $progress = $this->advance($request, $session, $step);
        } else {
            $session->update(['step_state' => ['step_id' => $step->id] + $verdict['state']]);
            $progress = [
                'steps_completed' => $session->steps_completed,
                'step_count' => $session->experiment->steps()->count(),
                'quiz_unlocked' => false,
            ];
        }

        // FR-06.3, FR-07.3: a move can finish a mission or earn a badge.
        $badges = $this->achievements->refresh($request->user());

        return response()->json([
            'badges_awarded' => Badges::describe($badges),
            'outcome' => $verdict['outcome'],
            'category' => $verdict['category'],
            'mistake_code' => $verdict['mistake_code'],
            'message' => $verdict['message'],
            'mood' => $progress['quiz_unlocked'] ? 'party' : $verdict['mood'],
            'step_complete' => $verdict['step_complete'],
            'mistakes' => $verdict['state']['mistakes'] ?? 0,
            'progress' => $verdict['state']['data'] ?? [],
            'reveal' => $verdict['reveal'],
            'steps_completed' => $progress['steps_completed'],
            'step_count' => $progress['step_count'],
            'quiz_unlocked' => $progress['quiz_unlocked'],
        ]);
    }

    /** What each kind must send (docs/experiment-engine.md, "action sent"). */
    private const ACTION_RULES = [
        'gear' => ['action.item' => ['required', 'string', 'max:40']],
        'place' => ['action.item' => ['required', 'string', 'max:40'], 'action.zone' => ['required', 'string', 'max:40']],
        'choose' => ['action.item' => ['required', 'string', 'max:40']],
        'measure' => ['action.value' => ['required', 'numeric']],
        'add_until' => ['action.stop_at' => ['required', 'numeric']],
        'heat' => ['action.level' => ['required', 'string', 'max:20'], 'action.seconds' => ['required', 'integer', 'min:0', 'max:3600']],
        'read' => ['action.value' => ['required', 'numeric']],
        'observe' => ['action.option' => ['required', 'string', 'max:10']],
        'confirm' => [],
    ];

    /** FR-03.4: a step may not be worked on before the one in front of it is done. */
    private function outOfOrder(ExperimentSession $session, ExperimentStep $step): ?JsonResponse
    {
        $expected = count($session->completed_step_ids ?? []) + 1;

        if ($step->step_order === $expected) {
            return null;
        }

        return response()->json([
            'message' => "Complete step {$expected} first.",
            'code' => 'out_of_order',
        ], 422);
    }

    /** FR-03.5: marks a step done at the moment it happens; finishing opens the quiz. */
    private function advance(Request $request, ExperimentSession $session, ExperimentStep $step): array
    {
        $done = collect($session->completed_step_ids ?? [])->push($step->id)->unique()->values();
        $total = $session->experiment->steps()->count();
        $allDone = $done->count() >= $total;

        $session->update([
            'completed_step_ids' => $done->all(),
            'steps_completed' => $done->count(),
            'completed_at' => $allDone ? now() : null,
            'step_state' => null,
        ]);

        // FR-09.5: finishing the experiment completes any task set for it.
        if ($allDone) {
            $this->tasks->experimentCompleted($request->user(), $session->experiment_id);
        }

        return [
            'session' => $session->fresh(),
            'steps_completed' => $done->count(),
            'step_count' => $total,
            // FR-04.1: the quiz opens only once every step is done.
            'quiz_unlocked' => $allDone,
        ];
    }

    /** read steps with value_from: the volume the student actually stopped at in step N. */
    private function readingFrom(ExperimentSession $session, int $order): ?float
    {
        $action = StepAction::where('session_id', $session->id)
            ->whereIn('outcome', ['correct', 'revealed'])
            ->whereHas('step', fn ($q) => $q->where('step_order', $order))
            ->latest('id')->first();

        $value = $action?->action['stop_at'] ?? $action?->action['value'] ?? null;

        return $value === null ? null : (float) $value;
    }

    /** The resumable progress, without anything that would give an answer away. */
    private function publicStepState(ExperimentSession $session): ?array
    {
        $state = $session->step_state;

        if (! $state) {
            return null;
        }

        return [
            'step_id' => $state['step_id'] ?? null,
            'mistakes' => $state['mistakes'] ?? 0,
            'progress' => $state['data'] ?? [],
        ];
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
