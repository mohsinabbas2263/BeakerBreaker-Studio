<?php

namespace App\Services;

use App\Models\ExperimentSession;
use App\Models\QuizAttempt;
use App\Models\ReadinessReport;
use App\Models\SafetyAttempt;
use App\Models\StepAction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * M4, FR-08.1 to FR-08.5: the AI readiness report.
 *
 * The six dimensions are computed here, in our own code, from the student's
 * session, quiz and safety history. Only the classification is asked of Gemini,
 * and when Gemini does not answer the rule below decides (NFR-06), so a report
 * always generates.
 *
 * TWO SETS OF FORMULAS, and each report records which it used (formula_version):
 *
 *  - engine-1: the student has played game steps, so step_actions holds every move.
 *    The formulas of docs/experiment-engine.md apply.
 *  - interim-1: the student has only old press-the-button experiments. Reagent
 *    management and error recovery are then estimated from session and quiz
 *    history (decided 8 Nov 2026), and the report marks them as estimated.
 */
class ReadinessService
{
    /** FR-08.4: no report under this many completed experiments. */
    public const MIN_COMPLETED = 3;

    /** FR-08.2 allows 5 seconds for the whole report; Gemini gets most of it. */
    public const AI_TIMEOUT_SECONDS = 4;

    public const ENGINE = 'engine-1';

    public const INTERIM = 'interim-1';

    /** The dimensions each formula set only estimates. */
    public const ESTIMATED = [
        self::ENGINE => [],
        self::INTERIM => ['reagent_management', 'error_recovery'],
    ];

    /** Kinds that test handling chemicals: what reagent management measures. */
    private const REAGENT_KINDS = ['measure', 'choose', 'add_until'];

    /** Which formulas apply: the engine's once any game step has been played. */
    public function formulaVersion(User $student): string
    {
        return StepAction::where('user_id', $student->id)->where('kind', '!=', 'confirm')->exists()
            ? self::ENGINE : self::INTERIM;
    }

    public const CLASSIFICATIONS = ['ready', 'developing', 'not_ready'];

    /** Distinct experiments this student has finished every step of. */
    public function completedCount(User $student): int
    {
        return ExperimentSession::where('user_id', $student->id)
            ->whereNotNull('completed_at')->distinct()->count('experiment_id');
    }

    /** FR-08.5: compute, classify and store one report. Assumes eligibility was checked. */
    public function generate(User $student, User $requester): ReadinessReport
    {
        $version = $this->formulaVersion($student);
        $dimensions = $this->dimensions($student);
        [$classification, $narrative, $fromAi] = $this->classify($student, $dimensions);

        return ReadinessReport::create([
            'subject_user_id' => $student->id,
            'requesting_user_id' => $requester->id,
            'classification' => $classification,
            'narrative' => $narrative,
            'ai_generated' => $fromAi,
            'formula_version' => $version,
            'generated_at' => now(),
            ...$dimensions,
        ]);
    }

    /**
     * FR-08.1, each value 0 to 100.
     *
     * @return array<string, int>
     */
    public function dimensions(User $student): array
    {
        $interim = $this->interimDimensions($student);

        if ($this->formulaVersion($student) === self::INTERIM) {
            return $interim;
        }

        return $this->engineDimensions($student, $interim);
    }

    /**
     * docs/experiment-engine.md, "Readiness, from step_actions". A step counts once
     * per session; time efficiency is the same in both sets.
     *
     * @param  array<string, int>  $interim  used where the game record has nothing to say
     * @return array<string, int>
     */
    private function engineDimensions(User $student, array $interim): array
    {
        $steps = StepAction::where('user_id', $student->id)->where('kind', '!=', 'confirm')
            ->with('session:id,experiment_id')
            ->orderBy('id')->get()
            ->groupBy(fn (StepAction $a) => "{$a->session_id}:{$a->step_id}");

        $finished = $steps->filter(fn (Collection $moves) => $moves->contains(fn ($m) => in_array($m->outcome, ['correct', 'revealed'], true)));
        $clean = fn (Collection $moves) => ! $moves->contains('outcome', 'mistake');
        $rightFirstTime = fn (Collection $moves) => $moves->first()->outcome === 'correct';

        // Procedural accuracy: steps completed with no mistake ÷ steps completed.
        $procedural = $this->percent($finished->filter($clean)->count(), $finished->count());

        // Safety compliance: 100 minus 25 per safety mistake, per experiment, averaged, floor 0.
        $safety = $steps->flatten()->groupBy(fn (StepAction $a) => $a->session?->experiment_id)
            ->map(fn (Collection $moves) => max(0, 100 - 25 * $moves->where('category', 'safety')->count()))
            ->avg();

        // Reagent management: measure, choose and add_until steps right first time ÷ all of them.
        $reagentSteps = $steps->filter(fn (Collection $moves) => in_array($moves->first()->kind, self::REAGENT_KINDS, true));
        $reagent = $reagentSteps->isEmpty()
            ? $interim['reagent_management']
            : $this->percent($reagentSteps->filter($rightFirstTime)->count(), $reagentSteps->count());

        // Conceptual understanding: first quiz attempts and observe steps right first time, averaged.
        $observe = $steps->filter(fn (Collection $moves) => $moves->first()->kind === 'observe');
        $conceptual = $observe->isEmpty()
            ? $interim['conceptual_understanding']
            : (int) round(($interim['conceptual_understanding'] + $this->percent($observe->filter($rightFirstTime)->count(), $observe->count())) / 2);

        // Error recovery: steps with a mistake then finished without the answer shown ÷ steps with a mistake.
        $slipped = $steps->reject($clean);
        $recovery = $slipped->isEmpty()
            ? 100
            : $this->percent($slipped->filter(fn (Collection $moves) => $moves->contains('outcome', 'correct'))->count(), $slipped->count());

        return [
            'procedural_accuracy' => $procedural,
            'safety_compliance' => (int) round($safety ?? 100),
            'reagent_management' => $reagent,
            'time_efficiency' => $interim['time_efficiency'],
            'conceptual_understanding' => $conceptual,
            'error_recovery' => $recovery,
        ];
    }

    /**
     * The interim set, from session, quiz and safety history only.
     *
     * @return array<string, int>
     */
    private function interimDimensions(User $student): array
    {
        $sessions = ExperimentSession::where('user_id', $student->id)
            ->with('experiment:id,estimated_minutes')
            ->orderBy('started_at')->orderBy('id')->get();
        $byExperiment = $sessions->groupBy('experiment_id');
        $completedIds = $sessions->whereNotNull('completed_at')->pluck('experiment_id')->unique();

        $quizzes = QuizAttempt::where('user_id', $student->id)
            ->orderBy('submitted_at')->orderBy('id')->get()->groupBy('experiment_id');

        return [
            // Experiments carried through to the end, out of those begun.
            'procedural_accuracy' => $this->percent($completedIds->count(), $byExperiment->count()),

            'safety_compliance' => $this->safetyCompliance($student),

            // Interim: finished in the first sitting rather than abandoned and restarted.
            'reagent_management' => $this->percent(
                $completedIds->filter(fn ($id) => $byExperiment[$id]->first()->completed_at !== null)->count(),
                $completedIds->count(),
            ),

            // Estimated minutes over actual minutes, capped at 100. Never affects points (FR-06.2).
            'time_efficiency' => $this->timeEfficiency($completedIds, $byExperiment),

            // The first quiz attempt of each experiment, before any retry.
            'conceptual_understanding' => (int) round(
                $quizzes->map(fn (Collection $tries) => $tries->first()->score)->avg() ?? 0
            ),

            // Interim: quizzes failed the first time and passed later; 100 when none failed.
            'error_recovery' => $this->errorRecovery($quizzes),
        ];
    }

    /**
     * FR-08.3, rule used whenever Gemini does not answer: average of the six at
     * least 80 and safety at least 90 is Ready; average at least 60 is
     * Approaching; otherwise Not Ready.
     */
    public function ruleClassification(array $dimensions): string
    {
        $average = array_sum($dimensions) / count($dimensions);

        if ($average >= 80 && $dimensions['safety_compliance'] >= 90) {
            return 'ready';
        }

        return $average >= 60 ? 'developing' : 'not_ready';
    }

    // ---- classification ------------------------------------------------------

    /** @return array{0: string, 1: string, 2: bool} classification, narrative, from AI */
    protected function classify(User $student, array $dimensions): array
    {
        $rule = $this->ruleClassification($dimensions);
        $key = config('services.gemini.key');

        if (filled($key)) {
            try {
                $response = Http::timeout(self::AI_TIMEOUT_SECONDS)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->post(config('services.gemini.endpoint'), [
                        'contents' => [['parts' => [['text' => $this->prompt($dimensions)]]]],
                        'generationConfig' => ['responseMimeType' => 'application/json'],
                    ]);

                $answer = json_decode((string) data_get($response->json(), 'candidates.0.content.parts.0.text'), true);
                $classification = $answer['classification'] ?? null;
                $narrative = trim((string) ($answer['narrative'] ?? ''));

                if ($response->successful() && in_array($classification, self::CLASSIFICATIONS, true) && $narrative !== '') {
                    // Safety comes first: whatever the model says, a student under 90 on
                    // safety is never sent to a real lab as Ready.
                    if ($classification === 'ready' && $dimensions['safety_compliance'] < 90) {
                        $classification = 'developing';
                    }

                    return [$classification, mb_substr(strip_tags($narrative), 0, 600), true];
                }
            } catch (Throwable $e) {
                Log::warning('Readiness classification fell back to the rule (NFR-06).', [
                    'student_id' => $student->id, 'reason' => $e->getMessage(),
                ]);
            }
        }

        return [$rule, $this->ruleNarrative($rule, $dimensions), false];
    }

    protected function prompt(array $dimensions): string
    {
        $lines = collect($dimensions)->map(fn ($v, $k) => '- '.str_replace('_', ' ', $k).": {$v}/100")->implode("\n");

        return implode("\n", [
            'You assess whether a secondary school chemistry student in Pakistan is ready to work in a real school laboratory,',
            'based only on these scores from a virtual laboratory (0 to 100, higher is better):',
            $lines,
            'Classify as exactly one of: "ready" (Ready for Real Lab), "developing" (Approaching Readiness), "not_ready" (Not Ready).',
            'Safety matters most: below 90 on safety compliance is never "ready".',
            'Write a narrative of two or three short sentences in simple English, addressed to the student\'s teacher:',
            'name the strongest and the weakest dimension and one thing to practise next. Do not invent facts beyond the scores.',
            'Answer only with JSON: {"classification": "...", "narrative": "..."}',
        ]);
    }

    /** The narrative written without AI, from the same numbers. */
    protected function ruleNarrative(string $classification, array $dimensions): string
    {
        $label = fn (string $k) => str_replace('_', ' ', $k);
        arsort($dimensions);
        $best = array_key_first($dimensions);
        $worst = array_key_last($dimensions);

        $opening = match ($classification) {
            'ready' => 'Ready for the real lab.',
            'developing' => 'Approaching readiness.',
            default => 'Not ready for the real lab yet.',
        };

        return "{$opening} Strongest in {$label($best)} ({$dimensions[$best]}); "
            ."weakest in {$label($worst)} ({$dimensions[$worst]}), which is the area to practise next.";
    }

    // ---- dimension helpers -----------------------------------------------------

    private function percent(int $part, int $whole): int
    {
        return $whole === 0 ? 0 : (int) round(100 * $part / $whole);
    }

    private function safetyCompliance(User $student): int
    {
        $first = SafetyAttempt::where('user_id', $student->id)
            ->orderBy('attempted_at')->orderBy('id')->get()
            ->groupBy('tier_number')
            ->map(fn (Collection $tries) => $tries->first()->score);

        return (int) round($first->avg() ?? 0);
    }

    private function timeEfficiency(Collection $completedIds, Collection $byExperiment): int
    {
        $ratios = $completedIds->map(function ($id) use ($byExperiment) {
            $session = $byExperiment[$id]->whereNotNull('completed_at')->first();
            $estimate = max(1, (int) ($session->experiment?->estimated_minutes ?? 15));
            $actual = max(1, $session->started_at->diffInMinutes($session->completed_at, true));

            return min(100, 100 * $estimate / $actual);
        });

        return (int) round($ratios->avg() ?? 0);
    }

    private function errorRecovery(Collection $quizzes): int
    {
        $failedFirst = $quizzes->filter(fn (Collection $tries) => ! $tries->first()->passed);

        if ($failedFirst->isEmpty()) {
            return 100;
        }

        return $this->percent(
            $failedFirst->filter(fn (Collection $tries) => $tries->contains('passed', true))->count(),
            $failedFirst->count(),
        );
    }
}
