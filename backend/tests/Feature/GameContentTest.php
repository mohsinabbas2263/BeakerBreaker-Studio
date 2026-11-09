<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExperimentStep;
use App\Services\StepJudge;
use Database\Seeders\ExperimentSeeder;
use Database\Seeders\GameExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The written game content (database/seeders/game/*.php) seeds, and every step of
 * every experiment can actually be completed with its own right answer. A typo in
 * an answer would otherwise leave a student stuck on a step forever.
 */
class GameContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ExperimentSeeder::class, GameExperimentSeeder::class]);
    }

    public function test_all_game_experiments_are_published_with_their_class_and_board(): void
    {
        $slugs = collect((new GameExperimentSeeder)->load())->pluck('slug');

        $this->assertCount(34, $slugs);
        $this->assertSame(34, Experiment::whereIn('slug', $slugs)->where('is_published', true)->count());

        // Every published experiment is now a game experiment from the books.
        $this->assertSame(34, Experiment::where('is_published', true)->count());
        $this->assertSame(12, Experiment::where('slug', 'f12-forensic-ink')->value('grade_level'));
        $this->assertSame('punjab', Experiment::where('slug', 'p11-acid-radicals')->value('board'));
    }

    public function test_replaced_old_experiments_are_unpublished_but_kept(): void
    {
        foreach (array_keys(GameExperimentSeeder::SUPERSEDED) as $slug) {
            $this->assertFalse((bool) Experiment::where('slug', $slug)->value('is_published'), $slug);
        }
    }

    public function test_seeding_twice_changes_nothing(): void
    {
        $steps = ExperimentStep::count();

        $this->seed(GameExperimentSeeder::class);

        $this->assertSame($steps, ExperimentStep::count());
    }

    public function test_every_step_of_every_game_experiment_can_be_completed(): void
    {
        $judge = new StepJudge;
        $slugs = collect((new GameExperimentSeeder)->load())->pluck('slug');

        foreach (Experiment::whereIn('slug', $slugs)->with('steps')->get() as $experiment) {
            $readings = [];

            foreach ($experiment->steps->sortBy('step_order') as $step) {
                $where = "{$experiment->slug} step {$step->step_order} ({$step->kind})";
                $this->assertNotEmpty($step->preauthored_guidance, "{$where}: guidance (NFR-06)");
                $this->assertNotEmpty($step->messages['intro'] ?? null, "{$where}: intro line");

                $state = [];
                $result = null;
                foreach ($this->rightMoves($judge, $step, $readings) as $move) {
                    $result = $judge->judge($step, $move, $state, fn (int $n) => $readings[$n] ?? null);
                    $this->assertNotSame('mistake', $result['outcome'], "{$where}: the right answer was judged wrong");
                    $state = $result['state'];
                }

                $this->assertTrue($result['step_complete'] ?? false, "{$where}: never completes");

                $readings[$step->step_order] = $move['stop_at'] ?? $move['value'] ?? null;
            }
        }
    }

    /** The moves a student who knows the answer would make, built from the step's own config. */
    private function rightMoves(StepJudge $judge, ExperimentStep $step, array $readings): array
    {
        $c = $step->config ?? [];

        return match ($step->kind) {
            'gear' => array_map(fn ($i) => ['item' => $i], $c['required']),
            'place' => array_map(fn ($zone, $item) => ['item' => $item, 'zone' => $zone], array_keys($c['answer']), $c['answer']),
            'choose' => [['item' => $c['correct']]],
            'observe' => [['option' => $c['correct']]],
            'measure' => [['value' => $c['target']]],
            'add_until' => [['stop_at' => $c['end']]],
            'heat' => array_map(fn ($level) => ['level' => $level, 'seconds' => $c['min_seconds'] ?? 0], $c['sequence']),
            'read' => [['value' => isset($c['value_from']) ? $readings[(int) substr($c['value_from'], 5)] : $c['value']]],
            default => [[]],
        };
    }

    /**
     * FR-05.1: at least four experiments in AR, and one in each board's pair of
     * classes, so every licensed student has at least one AR experiment to open.
     */
    public function test_at_least_four_experiments_offer_ar_and_every_board_and_class_pair_has_one(): void
    {
        $ar = Experiment::where('is_published', true)->where('ar_available', true)->get();

        $this->assertGreaterThanOrEqual(4, $ar->count());

        foreach (['punjab', 'federal'] as $board) {
            foreach ([[9, 10], [11, 12]] as $pair) {
                $this->assertTrue(
                    $ar->contains(fn ($e) => in_array($e->board, [$board, 'both'], true) && in_array($e->grade_level, $pair, true)),
                    "no AR experiment for {$board} classes ".implode(' and ', $pair),
                );
            }
        }
    }
}
