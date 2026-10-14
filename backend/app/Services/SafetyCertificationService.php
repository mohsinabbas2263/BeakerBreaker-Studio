<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\SafetyAttempt;
use App\Models\SafetyTierQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Everything about safety certification that a client must never decide.
 *
 * FR-02.2 tier gating, FR-02.4 random selection, FR-02.5 server scoring,
 * FR-02.7 certificate issue, FR-02.8 no certificate below 80 and no revocation.
 */
class SafetyCertificationService
{
    public const PASS_MARK = 80;

    public const QUESTIONS_PER_ATTEMPT = 10;

    /** FR-02.2: tier 1 is open; every later tier needs the one before it passed. */
    public function tierIsAvailable(User $user, int $tier): bool
    {
        if ($tier < 1 || $tier > 4) {
            return false;
        }

        return $tier === 1 || $user->hasPassedSafetyTier($tier - 1);
    }

    /**
     * FR-02.4: a fresh random set on every attempt.
     * The answer key is not part of what this returns.
     */
    public function drawQuestions(int $tier): Collection
    {
        return SafetyTierQuestion::query()
            ->where('tier_number', $tier)
            ->inRandomOrder()
            ->limit(self::QUESTIONS_PER_ATTEMPT)
            ->get();
    }

    /**
     * FR-02.5: the score is computed here, from the stored answer key, and a
     * score supplied by a client is not a parameter of this method at all.
     *
     * @param  array<int,int>  $answers  question id => chosen option index
     */
    public function grade(User $user, int $tier, array $answers): SafetyAttempt
    {
        if (! $this->tierIsAvailable($user, $tier)) {
            throw new RuntimeException("Tier {$tier} is not available to this student (FR-02.2).");
        }

        $questions = SafetyTierQuestion::query()
            ->whereIn('id', array_keys($answers))
            ->where('tier_number', $tier)
            ->get();

        if ($questions->isEmpty()) {
            throw new RuntimeException('No questions of that tier were answered (SR-07).');
        }

        $correct = $questions->filter(
            fn (SafetyTierQuestion $q) => (int) ($answers[$q->id] ?? -1) === $q->correct_option
        )->count();

        $score = (int) round($correct / $questions->count() * 100);

        return SafetyAttempt::create([
            'user_id' => $user->id,
            'tier_number' => $tier,
            'score' => $score,
            'passed' => $score >= self::PASS_MARK,
            'question_ids' => $questions->pluck('id')->all(),
            'submitted_answers' => $answers,
            'attempted_at' => now(),
        ]);
    }

    /** The best score a student has recorded in each tier. */
    public function bestScores(User $user): array
    {
        return SafetyAttempt::query()
            ->where('user_id', $user->id)
            ->selectRaw('tier_number, MAX(score) as best')
            ->groupBy('tier_number')
            ->pluck('best', 'tier_number')
            ->all();
    }

    /**
     * FR-02.7: issued once all four tiers are passed.
     * FR-02.8: never issued when any tier is below 80, and never revoked.
     */
    public function issueCertificateIfEarned(User $user): ?Certificate
    {
        if ($existing = $user->certificate) {
            return $existing;                       // FR-02.8: no reissue, no overwrite
        }

        $best = $this->bestScores($user);

        foreach ([1, 2, 3, 4] as $tier) {
            if (($best[$tier] ?? 0) < self::PASS_MARK) {
                return null;
            }
        }

        return DB::transaction(fn () => Certificate::create([
            'user_id' => $user->id,
            'certificate_reference' => $this->reference(),
            'tier1_score' => $best[1],
            'tier2_score' => $best[2],
            'tier3_score' => $best[3],
            'tier4_score' => $best[4],
            'issued_on' => now()->toDateString(),
        ]));
    }

    /** FR-02.7: a unique identifier, readable aloud in a classroom. */
    protected function reference(): string
    {
        do {
            $ref = 'BBS-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (Certificate::where('certificate_reference', $ref)->exists());

        return $ref;
    }
}
