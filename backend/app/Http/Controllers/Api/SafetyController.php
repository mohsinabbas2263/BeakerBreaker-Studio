<?php

namespace App\Http\Controllers\Api;

use App\Models\SafetyTierQuestion;
use App\Services\RewardService;
use App\Services\SafetyCertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SafetyController extends Controller
{
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

        return response()->json([
            'tier_number' => $tier,
            'title' => SafetyTierQuestion::TIERS[$tier] ?? null,
            'questions' => $questions->map(fn ($q) => [
                'id' => $q->id,
                'prompt' => $q->prompt,
                'options' => $q->options,
            ])->values(),
        ]);
    }

    /**
     * FR-02.5: answers in, score computed here. A score in the request body
     * is not read, and sending one changes nothing.
     */
    public function submit(Request $request, int $tier): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        $user = $request->user();

        try {
            $attempt = $this->safety->grade($user, $tier, $validated['answers']);
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
