<?php

namespace App\Http\Controllers\Api;

use App\Models\Experiment;
use App\Models\ExperimentQuizQuestion;
use App\Models\QuizAttempt;
use App\Services\AchievementService;
use App\Services\RewardService;
use App\Support\Badges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public const PASS_MARK = 80;

    /** How long a handed-out quiz stays open for submission. */
    public const ATTEMPT_TTL_SECONDS = 7200;

    public static function attemptKey(string $id): string
    {
        return "quiz_attempt:{$id}";
    }

    public function __construct(
        protected RewardService $rewards,
        protected AchievementService $achievements,
    ) {}

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

        // FR-04.5 + randomise answer positions so correct is not always option A.
        // permMap[question_id] = [old_idx_at_new_pos_0, old_idx_at_new_pos_1, ...]
        $uuid    = (string) Str::uuid();
        $permMap = [];

        $mapped = $questions->map(function (ExperimentQuizQuestion $q) use (&$permMap) {
            $opts    = $q->options;
            $indices = range(0, count($opts) - 1);
            shuffle($indices);
            $permMap[$q->id] = $indices;

            return [
                'id'      => $q->id,
                'prompt'  => $q->prompt,
                'options' => array_values(array_map(fn ($i) => $opts[$i], $indices)),
            ];
        })->values();

        // FR-02.5, FR-04.4: the server keeps the shuffle; submit() scores against it.
        cache()->put(self::attemptKey($uuid), [
            'user_id' => $user->id,
            'experiment_id' => $experiment->id,
            'perm' => $permMap,
        ], self::ATTEMPT_TTL_SECONDS);

        return response()->json([
            'experiment'    => ['id' => $experiment->id, 'title' => $experiment->title],
            'pass_mark'     => self::PASS_MARK,
            '_quiz_session' => $uuid,
            'questions'     => $mapped,
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
            'answers'        => ['present', 'array'],
            'answers.*'      => ['required', 'integer', 'min:0', 'max:9'],
            'session_id'     => ['nullable', 'integer', 'exists:experiment_sessions,id'],
            '_quiz_session'  => ['required', 'string', 'max:40'],
        ]);

        $user    = $request->user();
        $answers = $validated['answers'];

        // Single use, and only for the student and experiment it was drawn for.
        $record = cache()->pull(self::attemptKey($validated['_quiz_session']));

        if (! is_array($record) || $record['user_id'] !== $user->id || $record['experiment_id'] !== $experiment->id) {
            return response()->json([
                'message' => 'This quiz has expired or was already submitted. Open it again.',
                'code' => 'attempt_expired',
            ], 422);
        }

        $permMap = $record['perm'];

        // FR-02.5: scored over every question that was handed out, not over the ones
        // the client chose to answer. An unanswered question counts as wrong.
        $questions = ExperimentQuizQuestion::query()
            ->where('experiment_id', $experiment->id)
            ->whereIn('id', array_keys($permMap))
            ->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'This quiz has no questions.'], 422);
        }

        $feedback = $questions->map(function (ExperimentQuizQuestion $q) use ($answers, $permMap) {
            $chosen = array_key_exists($q->id, $answers) ? (int) $answers[$q->id] : -1;

            // perm[shown_position] = stored_position.
            $perm           = $permMap[$q->id];
            $originalChosen = $perm[$chosen] ?? -1;
            $correct        = $originalChosen === $q->correct_option;

            // The correct answer's position as the student saw it.
            $shuffledCorrect = (int) array_search($q->correct_option, $perm, true);

            return [
                'question_id'    => $q->id,
                'chosen_option'  => $chosen,
                'correct'        => $correct,
                // FR-04.2: the explanation goes out only where it is needed.
                'explanation'    => $correct ? null : $q->explanation,
                'correct_option' => $correct ? null : $shuffledCorrect,
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
        $badges = $this->achievements->refresh($user);   // FR-06.3, FR-07.3

        return response()->json([
            'attempt_id' => $attempt->id,
            'badges_awarded' => Badges::describe($badges),
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
