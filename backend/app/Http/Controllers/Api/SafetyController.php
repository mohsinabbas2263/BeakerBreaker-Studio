<?php

namespace App\Http\Controllers\Api;

use App\Models\SafetyTierQuestion;
use App\Services\RewardService;
use App\Services\SafetyCertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class SafetyController extends Controller
{
    /** How long a drawn set stays open for submission. */
    public const ATTEMPT_TTL_SECONDS = 7200;

    public static function attemptKey(string $id): string
    {
        return "safety_attempt:{$id}";
    }

    public function __construct(
        protected SafetyCertificationService $safety,
        protected RewardService $rewards,
    ) {}

    /** FR-02.1 and FR-02.2: the four tiers, and which of them is open. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $best = $this->safety->bestScores($user);

        $tiers = collect(SafetyTierQuestion::TIERS)->map(fn ($title, $number) => [
            'tier_number' => $number,
            'title' => $title,
            'available' => $this->safety->tierIsAvailable($user, $number),
            'passed' => $user->hasPassedSafetyTier($number),
            'best_score' => $best[$number] ?? null,
            'question_count' => SafetyCertificationService::QUESTIONS_PER_ATTEMPT,
            'pass_mark' => SafetyCertificationService::PASS_MARK,
        ])->values();

        return response()->json([
            'tiers' => $tiers,
            'certificate' => $user->certificate,
            'highest_passed' => $user->highestSafetyTierPassed(),
        ]);
    }

    /** FR-02.4: a random set each time. The answer key stays here. */
    public function start(Request $request, int $tier): JsonResponse
    {
        $user = $request->user();

        if (! $this->safety->tierIsAvailable($user, $tier)) {
            return response()->json([
                'message' => 'Pass the previous tier with 80 percent or more first.',
                'code' => 'tier_locked',
            ], 403);
        }

        $questions = $this->safety->drawQuestions($tier);

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'This tier has no questions yet.'], 404);
        }

        // Shuffle answer positions so correct is not always option A (same
        // permutation pattern as QuizController).
        $uuid    = (string) Str::uuid();
        $permMap = [];

        $mapped = $questions->map(function ($q) use (&$permMap) {
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

        // FR-02.5: the server remembers which questions it drew and how it shuffled
        // them. submit() scores against this record, never against the client's list.
        cache()->put(self::attemptKey($uuid), [
            'user_id' => $user->id,
            'tier' => $tier,
            'perm' => $permMap,
        ], self::ATTEMPT_TTL_SECONDS);

        return response()->json([
            'tier_number'      => $tier,
            'title'            => SafetyTierQuestion::TIERS[$tier] ?? null,
            '_safety_session'  => $uuid,
            'questions'        => $mapped,
        ]);
    }

    /**
     * FR-02.5: answers in, score computed here. A score in the request body
     * is not read, and sending one changes nothing.
     */
    public function submit(Request $request, int $tier): JsonResponse
    {
        $validated = $request->validate([
            'answers'          => ['present', 'array'],
            'answers.*'        => ['required', 'integer', 'min:0', 'max:9'],
            '_safety_session'  => ['required', 'string', 'max:40'],
        ]);

        $user = $request->user();

        // Single use: an attempt can be submitted once, by the student it was drawn for,
        // for the tier it was drawn from.
        $record = cache()->pull(self::attemptKey($validated['_safety_session']));

        if (! is_array($record) || $record['user_id'] !== $user->id || $record['tier'] !== $tier) {
            return response()->json([
                'message' => 'This attempt has expired or was already submitted. Start the tier again.',
                'code' => 'attempt_expired',
            ], 422);
        }

        // Translate each shuffled position back to the stored option index.
        $answers = [];
        foreach ($record['perm'] as $questionId => $perm) {
            if (array_key_exists($questionId, $validated['answers'])) {
                $chosen = (int) $validated['answers'][$questionId];
                $answers[$questionId] = $perm[$chosen] ?? -1;
            }
        }

        try {
            $attempt = $this->safety->grade($user, $tier, array_keys($record['perm']), $answers);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        // FR-06.1: points for passing a tier. Never for the time it took (FR-06.2).
        $pointsAwarded = 0;
        if ($attempt->passed) {
            $alreadyPassed = $user->safetyAttempts()
                ->where('tier_number', $tier)
                ->where('passed', true)
                ->where('id', '!=', $attempt->id)
                ->exists();

            if (! $alreadyPassed) {
                $pointsAwarded = $this->rewards->awardPoints($user, 100, 'safety_tier', $tier);
                $this->rewards->awardBadge($user, "safety_tier_{$tier}");
            }
        }

        $this->rewards->recordActivity($user);

        // FR-02.7: issued the moment the fourth tier is passed, not a step later.
        $certificate = $this->safety->issueCertificateIfEarned($user->fresh());

        return response()->json([
            'score' => $attempt->score,
            'passed' => $attempt->passed,
            'pass_mark' => SafetyCertificationService::PASS_MARK,
            'points_awarded' => $pointsAwarded,
            'next_tier_available' => $this->safety->tierIsAvailable($user->fresh(), $tier + 1),
            'certificate' => $certificate,
            // FR-02.6: a failed tier may be reattempted without limit.
            'may_reattempt' => ! $attempt->passed,
        ]);
    }

    /** FR-02.9: the student's own certificate. */
    public function certificate(Request $request): JsonResponse
    {
        $certificate = $request->user()->certificate;

        return $certificate
            ? response()->json(['certificate' => $certificate])
            : response()->json(['message' => 'No certificate has been issued yet.'], 404);
    }
}
