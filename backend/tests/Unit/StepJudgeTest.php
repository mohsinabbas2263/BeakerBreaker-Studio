<?php

namespace Tests\Unit;

use App\Models\ExperimentStep;
use App\Services\StepJudge;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * NFR-10: judging a step is score computation, so every branch is covered here.
 * The configs mirror backend/database/seeders/game/*.php.
 */
class StepJudgeTest extends TestCase
{
    private StepJudge $judge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->judge = new StepJudge;
    }

    private function step(string $kind, array $config, array $messages = []): ExperimentStep
    {
        return new ExperimentStep([
            'kind' => $kind, 'config' => $config, 'preauthored_guidance' => 'Pre-authored hint.',
            'messages' => $messages + ['intro' => 'Go!', 'success' => 'Well done!'],
        ]);
    }

    // ---- gear ------------------------------------------------------------------

    public function test_gear_is_done_when_every_required_item_is_worn_and_forbidden_is_a_safety_mistake(): void
    {
        $step = $this->step('gear', ['items' => ['goggles', 'coat', 'sandals', 'gloves'], 'required' => ['goggles', 'coat'], 'forbidden' => ['sandals']],
            ['sandals' => 'Closed shoes only.']);

        $r = $this->judge->judge($step, ['item' => 'goggles']);
        $this->assertSame('partial', $r['outcome']);
        $this->assertSame('point', $r['mood']);
        $this->assertNull($r['message']);

        $r = $this->judge->judge($step, ['item' => 'sandals'], $r['state']);
        $this->assertSame(['mistake', 'safety', 'sandals', 'worried', 'Closed shoes only.'],
            [$r['outcome'], $r['category'], $r['mistake_code'], $r['mood'], $r['message']]);

        $r = $this->judge->judge($step, ['item' => 'gloves'], $r['state']);   // harmless extra
        $this->assertSame('partial', $r['outcome']);

        $r = $this->judge->judge($step, ['item' => 'goggles'], $r['state']);  // already worn
        $this->assertFalse($r['step_complete']);

        $r = $this->judge->judge($step, ['item' => 'coat'], $r['state']);
        $this->assertSame(['correct', true, 'happy', 'Well done!'], [$r['outcome'], $r['step_complete'], $r['mood'], $r['message']]);
        $this->assertSame([], $r['state']);
    }

    // ---- place -----------------------------------------------------------------

    public function test_place_judges_each_drop_and_completes_when_every_zone_is_right(): void
    {
        $step = $this->step('place', ['items' => ['burette', 'flask', 'beaker'], 'zones' => ['stand', 'under'],
            'answer' => ['stand' => 'burette', 'under' => 'flask']], ['beaker' => 'A beaker spills.']);

        $r = $this->judge->judge($step, ['item' => 'beaker', 'zone' => 'under']);
        $this->assertSame(['mistake', 'procedure', 'beaker', 'A beaker spills.'], [$r['outcome'], $r['category'], $r['mistake_code'], $r['message']]);

        $r = $this->judge->judge($step, ['item' => 'burette', 'zone' => 'nowhere'], $r['state']);
        $this->assertSame('mistake', $r['outcome']);
        $this->assertSame('Pre-authored hint.', $r['message']);   // no line for this code: NFR-06 fallback

        $r = $this->judge->judge($step, ['item' => 'burette', 'zone' => 'stand'], $r['state']);
        $this->assertSame('partial', $r['outcome']);

        $r = $this->judge->judge($step, ['item' => 'flask', 'zone' => 'under'], $r['state']);
        $this->assertTrue($r['step_complete']);
    }

    public function test_simultaneous_place_waits_for_every_zone_then_judges_them_together(): void
    {
        $step = $this->step('place', ['items' => ['cotton_nh3', 'cotton_hcl'], 'zones' => ['left_end', 'right_end'],
            'answer' => ['left_end' => 'cotton_nh3', 'right_end' => 'cotton_hcl'], 'simultaneous' => true]);

        $r = $this->judge->judge($step, ['item' => 'cotton_hcl', 'zone' => 'left_end']);
        $this->assertSame('partial', $r['outcome']);
        $r = $this->judge->judge($step, ['item' => 'cotton_nh3', 'zone' => 'right_end'], $r['state']);
        $this->assertSame(['mistake', 'cotton_hcl'], [$r['outcome'], $r['mistake_code']]);
        $this->assertSame([], $r['state']['data']['placed']);   // cleared for another try

        $r = $this->judge->judge($step, ['item' => 'cotton_nh3', 'zone' => 'left_end'], $r['state']);
        $r = $this->judge->judge($step, ['item' => 'cotton_hcl', 'zone' => 'right_end'], $r['state']);
        $this->assertTrue($r['step_complete']);
    }

    // ---- choose, observe -----------------------------------------------------------

    public function test_choose_uses_the_category_of_each_wrong_item(): void
    {
        $step = $this->step('choose', ['items' => ['naoh', 'hcl', 'water'], 'correct' => 'hcl', 'wrong' => ['naoh' => 'reagent']]);

        $this->assertSame('reagent', $this->judge->judge($step, ['item' => 'naoh'])['category']);
        $this->assertSame('procedure', $this->judge->judge($step, ['item' => 'water'])['category']);
        $this->assertTrue($this->judge->judge($step, ['item' => 'hcl'])['step_complete']);
    }

    public function test_observe_is_a_concept_question(): void
    {
        $step = $this->step('observe', ['options' => ['a' => 'x', 'b' => 'y'], 'correct' => 'b']);

        $wrong = $this->judge->judge($step, ['option' => 'a']);
        $this->assertSame(['mistake', 'concept', 'wrong'], [$wrong['outcome'], $wrong['category'], $wrong['mistake_code']]);
        $this->assertSame('correct', $this->judge->judge($step, ['option' => 'b'])['outcome']);
    }

    // ---- measure, add_until, read ------------------------------------------------------

    public function test_measure_accepts_within_tolerance_and_names_the_direction_of_a_miss(): void
    {
        $step = $this->step('measure', ['unit' => 'mL', 'target' => 25.0, 'tolerance' => 0.5]);

        $this->assertSame('too_low', $this->judge->judge($step, ['value' => 24])['mistake_code']);
        $this->assertSame('too_high', $this->judge->judge($step, ['value' => 26])['mistake_code']);
        $this->assertSame('reagent', $this->judge->judge($step, ['value' => 26])['category']);
        $this->assertSame('correct', $this->judge->judge($step, ['value' => 24.5])['outcome']);
        $this->assertSame('correct', $this->judge->judge($step, ['value' => 25.5])['outcome']);
    }

    public function test_an_exact_measure_accepts_only_the_target_despite_float_error(): void
    {
        $step = $this->step('measure', ['target' => 0.3, 'tolerance' => 0]);

        $this->assertSame('correct', $this->judge->judge($step, ['value' => 0.1 + 0.2])['outcome']);
        $this->assertSame('mistake', $this->judge->judge($step, ['value' => 0.31])['outcome']);
    }

    public function test_add_until_too_early_is_procedure_and_overshoot_is_reagent(): void
    {
        $step = $this->step('add_until', ['unit' => 'mL', 'end' => 18.4, 'tolerance' => 0.3]);

        $early = $this->judge->judge($step, ['stop_at' => 17.9]);
        $this->assertSame(['too_early', 'procedure'], [$early['mistake_code'], $early['category']]);
        $over = $this->judge->judge($step, ['stop_at' => 18.8]);
        $this->assertSame(['overshoot', 'reagent'], [$over['mistake_code'], $over['category']]);
        $this->assertSame('correct', $this->judge->judge($step, ['stop_at' => 18.7])['outcome']);
    }

    public function test_read_uses_a_fixed_value_or_the_students_own_earlier_reading(): void
    {
        $fixed = $this->step('read', ['unit' => '°C', 'value' => 78, 'tolerance' => 1]);
        $this->assertSame('correct', $this->judge->judge($fixed, ['value' => 77])['outcome']);
        $this->assertSame(['mistake', 'procedure'], array_values(array_intersect_key(
            $this->judge->judge($fixed, ['value' => 75]), array_flip(['outcome', 'category']))));

        $fromStep = $this->step('read', ['unit' => 'mL', 'value_from' => 'step:6', 'tolerance' => 0.1]);
        $reading = fn (int $order) => $order === 6 ? 18.6 : null;
        $this->assertSame('correct', $this->judge->judge($fromStep, ['value' => 18.6], [], $reading)['outcome']);
        $this->assertSame('mistake', $this->judge->judge($fromStep, ['value' => 18.4], [], $reading)['outcome']);

        // No earlier reading to compare with: never accepted.
        $this->assertSame('mistake', $this->judge->judge($fromStep, ['value' => 18.6], [], fn () => null)['outcome']);
    }

    // ---- heat ------------------------------------------------------------------------

    public function test_heat_follows_the_sequence_and_minimum_time(): void
    {
        $step = $this->step('heat', ['levels' => ['low', 'high'], 'sequence' => ['low', 'high'], 'min_seconds' => 20]);

        $r = $this->judge->judge($step, ['level' => 'high', 'seconds' => 30]);
        $this->assertSame(['too_hot_first', 'safety', 'worried'], [$r['mistake_code'], $r['category'], $r['mood']]);

        $r = $this->judge->judge($step, ['level' => 'low', 'seconds' => 5], $r['state']);
        $this->assertSame(['too_short', 'procedure'], [$r['mistake_code'], $r['category']]);

        $r = $this->judge->judge($step, ['level' => 'low', 'seconds' => 25], $r['state']);
        $this->assertSame('partial', $r['outcome']);

        $r = $this->judge->judge($step, ['level' => 'low', 'seconds' => 25], $r['state']);
        $this->assertSame('wrong_level', $r['mistake_code']);   // cooler than the stage asks

        $r = $this->judge->judge($step, ['level' => 'high', 'seconds' => 25], $r['state']);
        $this->assertTrue($r['step_complete']);
    }

    // ---- confirm, reveal, errors ---------------------------------------------------------

    public function test_confirm_is_always_correct(): void
    {
        $r = $this->judge->judge($this->step('confirm', []), []);

        $this->assertSame(['correct', true], [$r['outcome'], $r['step_complete']]);
    }

    public function test_after_three_mistakes_the_answer_is_revealed_and_doing_it_is_recorded_as_revealed(): void
    {
        $step = $this->step('choose', ['items' => ['a', 'b'], 'correct' => 'b']);

        $state = [];
        foreach ([1, 2] as $n) {
            $r = $this->judge->judge($step, ['item' => 'a'], $state);
            $this->assertNull($r['reveal']);
            $state = $r['state'];
        }
        $r = $this->judge->judge($step, ['item' => 'a'], $state);
        $this->assertSame(3, $r['state']['mistakes']);
        $this->assertSame(['correct' => 'b'], $r['reveal']);

        $done = $this->judge->judge($step, ['item' => 'b'], $r['state']);
        $this->assertSame('revealed', $done['outcome']);
        $this->assertTrue($done['step_complete']);
    }

    public function test_every_kind_has_a_reveal(): void
    {
        $cases = [
            ['gear', ['required' => ['goggles']], ['required' => ['goggles']]],
            ['place', ['answer' => ['stand' => 'burette']], ['answer' => ['stand' => 'burette']]],
            ['observe', ['correct' => 'c'], ['correct' => 'c']],
            ['measure', ['target' => 25], ['target' => 25]],
            ['add_until', ['end' => 18.4], ['end' => 18.4]],
            ['heat', ['sequence' => ['low'], 'min_seconds' => 10], ['sequence' => ['low'], 'min_seconds' => 10]],
            ['read', ['value' => 78], ['value' => 78]],
            ['confirm', [], []],
        ];

        foreach ($cases as [$kind, $config, $expected]) {
            $this->assertSame($expected, $this->judge->reveal($this->step($kind, $config)), $kind);
        }
    }

    public function test_an_unknown_kind_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->judge->judge($this->step('dance', []), []);
    }

    public function test_the_public_config_never_carries_an_answer(): void
    {
        foreach (ExperimentStep::PRIVATE_KEYS as $kind => $private) {
            $config = array_fill_keys($private, 'secret') + ['items' => ['x'], 'unit' => 'mL', 'end' => 18.4];
            $public = $this->step($kind, $config)->publicConfig();

            foreach ($private as $key) {
                $this->assertArrayNotHasKey($key, $public, "{$kind}.{$key}");
            }
            $this->assertArrayHasKey('items', $public);
        }
    }
}
