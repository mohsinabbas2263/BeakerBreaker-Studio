<?php

namespace App\Services;

use App\Models\ExperimentStep;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FR-03.7: guidance within 2 seconds, or 200 milliseconds when cached.
 * NFR-06: every experiment stays completable when the AI service is down.
 *
 * The pre-authored line in experiment_steps is not a degraded mode that
 * someone has to remember to build. It is the default this class falls back to
 * on an empty key, a timeout, an error, or a refusal.
 */
class GuidanceService
{
    public const TIMEOUT_SECONDS = 2;

    public function forStep(ExperimentStep $step, ?string $studentQuestion = null): array
    {
        if ($studentQuestion === null) {
            // The step opener is identical for every student, so it caches well.
            return Cache::remember(
                "guidance:step:{$step->id}",
                now()->addDay(),
                fn () => $this->fromAiOrFallback($step, null)
            );
        }

        return $this->fromAiOrFallback($step, $studentQuestion);
    }

    protected function fromAiOrFallback(ExperimentStep $step, ?string $question): array
    {
        $key = config('services.gemini.key');

        if (blank($key)) {
            return $this->fallback($step, 'no_key');
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders(['x-goog-api-key' => $key])
                ->post(config('services.gemini.endpoint'), [
                    'contents' => [[
                        'parts' => [['text' => $this->prompt($step, $question)]],
                    ]],
                ]);

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if ($response->successful() && filled($text)) {
                return ['text' => trim($text), 'source' => 'ai'];
            }

            return $this->fallback($step, 'empty_response');
        } catch (Throwable $e) {
            Log::warning('Guidance fell back to pre-authored text (NFR-06).', [
                'step_id' => $step->id,
                'reason' => $e->getMessage(),
            ]);

            return $this->fallback($step, 'exception');
        }
    }

    protected function fallback(ExperimentStep $step, string $reason): array
    {
        return [
            'text' => $step->preauthored_guidance,
            'source' => 'preauthored',
            'reason' => $reason,
        ];
    }

    /**
     * SR-09: the prompt keeps the assistant inside the virtual laboratory and
     * away from the acquisition or handling of real hazardous substances.
     */
    protected function prompt(ExperimentStep $step, ?string $question): string
    {
        $experiment = $step->experiment;

        return implode("\n", array_filter([
            'You are Beaky, a friendly chemistry lab assistant for a secondary school student in Pakistan.',
            'Answer in at most three short sentences, in simple English.',
            'Stay inside the simulated experiment. Never give instructions for obtaining, storing or',
            'handling hazardous chemicals outside this virtual laboratory, and never suggest substitutions.',
            "Experiment: {$experiment?->title}",
            "Current step {$step->step_order}: {$step->title}",
            "Step instruction: {$step->instruction_text}",
            "Approved guidance for this step: {$step->preauthored_guidance}",
            $question ? "The student asks: {$question}" : 'Introduce this step encouragingly.',
        ]));
    }
}
