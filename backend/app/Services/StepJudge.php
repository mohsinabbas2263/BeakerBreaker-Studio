<?php

namespace App\Services;

use App\Models\ExperimentStep;
use Closure;
use InvalidArgumentException;

/**
 * docs/experiment-engine.md: judges one move inside a game step.
 *
 * FR-02.5 and SR-07: the client sends only what the student did. Right and wrong
 * are decided here, against answers that never leave the server, and the result
 * is what gets recorded in step_actions.
 *
 * Pure: it reads the step, the move and the progress so far, and returns the
 * verdict with the new progress. Saving is the caller's job.
 */
class StepJudge
{
    /** After this many mistakes on one step the answer is shown, and the student does it. */
    public const REVEAL_AFTER = 3;

    private const FLOAT_EPSILON = 1e-6;

    /**
     * @param  array{mistakes?:int, revealed?:bool, data?:array}  $state  progress inside this step
     * @param  Closure(int):(float|null)|null  $readingOf  value the student produced in step N (for read value_from)
     * @return array{outcome:string, category:?string, mistake_code:?string, message:?string,
     *               mood:string, step_complete:bool, state:array, reveal:?array}
     */
    public function judge(ExperimentStep $step, array $action, array $state = [], ?Closure $readingOf = null): array
    {
        $state += ['mistakes' => 0, 'revealed' => false, 'data' => []];
        $config = $step->config ?? [];

        [$verdict, $code, $category, $complete, $data] = match ($step->kind ?? 'confirm') {
            'gear' => $this->gear($config, $action, $state['data']),
            'place' => $this->place($config, $action, $state['data']),
            'choose' => $this->choose($config, $action),
            'measure' => $this->measure($config, $action),
            'add_until' => $this->addUntil($config, $action),
            'heat' => $this->heat($config, $action, $state['data']),
            'read' => $this->read($config, $action, $readingOf),
            'observe' => $this->observe($config, $action),
            'confirm' => ['correct', null, null, true, []],
            default => throw new InvalidArgumentException("Unknown step kind {$step->kind}."),
        };

        $state['data'] = $data;

        if ($verdict === 'mistake') {
            $state['mistakes']++;
            if ($state['mistakes'] >= self::REVEAL_AFTER) {
                $state['revealed'] = true;
            }
        }

        // Done after the answer was shown: recorded as revealed, not as correct.
        $outcome = $verdict === 'correct' && $complete && $state['revealed'] ? 'revealed' : $verdict;

        return [
            'outcome' => $outcome,
            'category' => $verdict === 'mistake' ? $category : null,
            'mistake_code' => $verdict === 'mistake' ? $code : null,
            'message' => match (true) {
                $complete => $step->message('success'),
                $verdict === 'mistake' => $step->message($code ?? 'wrong'),
                default => $step->messages['partial'] ?? null,
            },
            'mood' => $this->mood($verdict, $category, $complete),
            'step_complete' => $complete,
            'state' => $complete ? [] : $state,
            'reveal' => ! $complete && $state['revealed'] ? $this->reveal($step) : null,
        ];
    }

    /** The answer, shown only once REVEAL_AFTER mistakes have been made on the step. */
    public function reveal(ExperimentStep $step): array
    {
        $c = $step->config ?? [];

        return match ($step->kind) {
            'gear' => ['required' => $c['required'] ?? []],
            'place' => ['answer' => $c['answer'] ?? []],
            'choose', 'observe' => ['correct' => $c['correct'] ?? null],
            'measure' => ['target' => $c['target'] ?? null],
            'add_until' => ['end' => $c['end'] ?? null],
            'heat' => ['sequence' => $c['sequence'] ?? [], 'min_seconds' => $c['min_seconds'] ?? 0],
            'read' => ['value' => $c['value'] ?? null],
            default => [],
        };
    }

    // ---- the nine kinds --------------------------------------------------------
    // Each returns [verdict, mistake_code, category, step_complete, data].

    private function gear(array $c, array $a, array $data): array
    {
        $item = (string) ($a['item'] ?? '');
        $worn = $data['worn'] ?? [];

        if (in_array($item, $c['forbidden'] ?? [], true)) {
            return ['mistake', $item, 'safety', false, $data];
        }

        if (in_array($item, $c['required'] ?? [], true) && ! in_array($item, $worn, true)) {
            $worn[] = $item;
        }

        $done = array_diff($c['required'] ?? [], $worn) === [];

        return [$done ? 'correct' : 'partial', null, null, $done, ['worn' => array_values($worn)]];
    }

    private function place(array $c, array $a, array $data): array
    {
        $item = (string) ($a['item'] ?? '');
        $zone = (string) ($a['zone'] ?? '');
        $answer = $c['answer'] ?? [];
        $placed = $data['placed'] ?? [];

        if (! array_key_exists($zone, $answer)) {
            return ['mistake', $item, 'procedure', false, $data];
        }

        if (! empty($c['simultaneous'])) {
            // All zones are filled first and judged together (the diffusion tube).
            $placed[$zone] = $item;
            if (count($placed) < count($answer)) {
                return ['partial', null, null, false, ['placed' => $placed]];
            }
            foreach ($answer as $z => $right) {
                if (($placed[$z] ?? null) !== $right) {
                    return ['mistake', $placed[$z] ?? $item, 'procedure', false, ['placed' => []]];
                }
            }

            return ['correct', null, null, true, ['placed' => $placed]];
        }

        if ($answer[$zone] !== $item) {
            return ['mistake', $item, 'procedure', false, $data];
        }

        $placed[$zone] = $item;
        $done = count($placed) === count($answer);

        return [$done ? 'correct' : 'partial', null, null, $done, ['placed' => $placed]];
    }

    private function choose(array $c, array $a): array
    {
        $item = (string) ($a['item'] ?? '');

        if ($item === ($c['correct'] ?? null)) {
            return ['correct', null, null, true, []];
        }

        return ['mistake', $item, $c['wrong'][$item] ?? 'procedure', false, []];
    }

    private function measure(array $c, array $a): array
    {
        $value = (float) ($a['value'] ?? 0);
        $target = (float) ($c['target'] ?? 0);

        if (abs($value - $target) <= (float) ($c['tolerance'] ?? 0) + self::FLOAT_EPSILON) {
            return ['correct', null, null, true, []];
        }

        return ['mistake', $value < $target ? 'too_low' : 'too_high', 'reagent', false, []];
    }

    private function addUntil(array $c, array $a): array
    {
        $stop = (float) ($a['stop_at'] ?? 0);
        $end = (float) ($c['end'] ?? 0);

        if ($stop < $end - self::FLOAT_EPSILON) {
            return ['mistake', 'too_early', 'procedure', false, []];
        }

        if ($stop > $end + (float) ($c['tolerance'] ?? 0) + self::FLOAT_EPSILON) {
            return ['mistake', 'overshoot', 'reagent', false, []];
        }

        return ['correct', null, null, true, []];
    }

    private function heat(array $c, array $a, array $data): array
    {
        $levels = $c['levels'] ?? ['low', 'high'];
        $sequence = $c['sequence'] ?? [$levels[0]];
        $stage = (int) ($data['stage'] ?? 0);
        $level = (string) ($a['level'] ?? '');
        $expected = $sequence[$stage] ?? end($sequence);

        if ($level !== $expected) {
            $hotter = array_search($level, $levels, true) > array_search($expected, $levels, true);

            return $hotter
                ? ['mistake', 'too_hot_first', 'safety', false, ['stage' => $stage]]
                : ['mistake', 'wrong_level', 'procedure', false, ['stage' => $stage]];
        }

        if ((int) ($a['seconds'] ?? 0) < (int) ($c['min_seconds'] ?? 0)) {
            return ['mistake', 'too_short', 'procedure', false, ['stage' => $stage]];
        }

        $stage++;
        $done = $stage >= count($sequence);

        return [$done ? 'correct' : 'partial', null, null, $done, ['stage' => $stage]];
    }

    private function read(array $c, array $a, ?Closure $readingOf): array
    {
        $true = isset($c['value_from']) && preg_match('/^step:(\d+)$/', (string) $c['value_from'], $m) && $readingOf
            ? $readingOf((int) $m[1])
            : ($c['value'] ?? null);

        $value = (float) ($a['value'] ?? 0);

        if ($true !== null && abs($value - (float) $true) <= (float) ($c['tolerance'] ?? 0) + self::FLOAT_EPSILON) {
            return ['correct', null, null, true, []];
        }

        return ['mistake', 'wrong', 'procedure', false, []];
    }

    private function observe(array $c, array $a): array
    {
        if ((string) ($a['option'] ?? '') === (string) ($c['correct'] ?? '')) {
            return ['correct', null, null, true, []];
        }

        return ['mistake', 'wrong', 'concept', false, []];
    }

    /** Which avatar animation the runner plays. A safety mistake always looks worried. */
    private function mood(string $verdict, ?string $category, bool $complete): string
    {
        return match (true) {
            $verdict === 'mistake' && $category === 'safety' => 'worried',
            $verdict === 'mistake' => 'oops',
            $complete => 'happy',
            default => 'point',
        };
    }
}
