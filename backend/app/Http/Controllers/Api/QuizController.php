<?php

namespace App\Http\Controllers\Api;

use App\Models\Experiment;
use App\Models\ExperimentQuizQuestion;
use App\Models\QuizAttempt;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public const PASS_MARK = 80;

    public function __construct(protected RewardService $rewards) {}

    /** FR-04.1: available only after every step is complete. FR-04.5: fresh order each time. */
    public function show(Request $request, Experiment $experiment): JsonResponse
    {
        $user = $request->user();

        $finished = $user->experimentSessions()
            ->where('experiment_id', $experiment->id)
            ->whereNotNull('completed_at')
            ->exists();

        if (! $finished) {
            return response()->json([
                'message' => 'Finish every step of the experiment before the quiz opens.',
                'code' => 'steps_incomplete',
            ], 403);
        }

        $questions = $experiment->quizQuestions()->inRandomOrder()->get();

        return response()->json([
            'experiment' => ['id' => $experiment->id, 'title' => $experiment->title],
            'pass_mark' => self::PASS_MARK,
            'questions' => $questions->map(fn (ExperimentQuizQuestion $q) => [
                'id' => $q->id,
                'prompt' => $q->prompt,
                'options' => $q->options,
            ])->values(),
        ]);
    }

    /**
     * FR-02.5 and SR-07: marked on the server against the stored key.
     * FR-04.2: each answer comes back with correct or incorrect, and an
     * explanation where it was wrong.
     * FR-04.3: the experiment's points are awarded once, on the first pass.
     */
    public function submit(Request $request, Experiment $experiment): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'min:0', 'max:9'],
            'session_id' => ['nullable', 'integer', 'exists:experiment_sessions,id'],
        ]);

        $user = $request->user();
        $answers = $validated['answers'];

        $questions = ExperimentQuizQuestion::query()
            ->where('experiment_id', $experiment->id)
            ->whereIn('id', array_keys($answers))
            ->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No questions of this experiment were answered.'], 422);
        }

        $feedback = $questions->map(function (ExperimentQuizQuestion $q) use ($answers) {
            $chosen = (int) ($answers[$q->id] ?? -1);
            $correct = $chosen === $q->correct_option;

            return [
                'question_id' => $q->id,
                'chosen_option' => $chosen,
                'correct' => $correct,
                // FR-04.2: the explanation goes out only where it is needed.
                'explanation' => $correct ? null : $q->explanation,
                'correct_option' => $correct ? null : $q->correct_option,
            ];
        });

        $score = (int) round($feedback->where('correct', true)->count() / $questions->count() * 100);
        $passed = $score >= self::PASS_MARK;

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'experiment_id' => $experiment->id,
            'session_id' => $validated['session_id'] ?? null,
            'score' => $score,
            'passed' => $passed,
            'submitted_answers' => $answers,
            'submitted_at' => now(),
        ]);

        $pointsAwarded = $passed
            ? $this->rewards->awardExperimentPointsOnce($user, $experiment->id, $experiment->base_points)
            : 0;

        if ($passed) {
            $this->rewards->awardBadge($user, "experiment_{$experiment->slug}");
        }

        $this->rewards->recordActivity($user);

        return response()->json([
            'attempt_id' => $attempt->id,
            'score' => $score,
            'passed' => $passed,
            'pass_mark' => self::PASS_MARK,
            'points_awarded' => $pointsAwarded,
            'feedback' => $feedback->values(),
            // FR-04.5: a failed quiz may be taken again.
            'may_reattempt' => ! $passed,
        ]);
    }
}
