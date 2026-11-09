<?php

namespace Database\Seeders;

use App\Models\Experiment;
use App\Models\ExperimentStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The textbook experiments in game format (docs/experiment-engine.md), Classes 9
 * to 12, Punjab and Federal. Content lives in database/seeders/game/*.php and is
 * written there in plain PHP arrays, so a teacher-reviewed change is a small diff.
 *
 * Runs after ExperimentSeeder, whose textbook filter unpublishes anything it does
 * not list; these are published here.
 */
class GameExperimentSeeder extends Seeder
{
    public const FILES = ['punjab', 'federal', 'federal-activities', 'class-11', 'class-12'];

    /**
     * Old-format experiments a game experiment replaces. They stay in the database,
     * unpublished, so past sessions and quiz attempts keep their experiment.
     * The eight Federal ones did not match their book pages (see federal-activities.php).
     */
    public const SUPERSEDED = [
        'standardise-naoh' => 'p11-standardise-naoh-game',
        'confirmatory-ion-tests' => 'p11-acid-radicals',
        'melting-points' => 'f9-water-melting-point',
        'boiling-points' => 'f9-water-boiling-point',
        'solubility-temperature' => 'f9-saturated-sugar-solution',
        'copper-sulphate-crystals' => 'f10-copper-sulphate-from-cuo',
        'dilution-hcl' => 'f10-dilute-hcl',
        'standardise-hcl' => 'f10-standardise-hcl',
        'acid-base-neutral' => 'f10-sodium-chloride-titration',
        'displacement-reactions' => 'f10-metal-reactivity',
    ];

    /** Card colours from the prototype's palette, by grade. */
    private const GRADIENTS = [
        9 => 'linear-gradient(135deg,#E8F4FD,#4FC3F7)',
        10 => 'linear-gradient(135deg,#C8F7E4,#4CAF82)',
        11 => 'linear-gradient(135deg,#FFF9C4,#FFD54F)',
        12 => 'linear-gradient(135deg,#FFD6C8,#FF8A50)',
    ];

    public function run(): void
    {
        $experiments = $this->load();

        DB::transaction(function () use ($experiments) {
            foreach ($experiments as $data) {
                $this->write($data);
            }

            Experiment::whereIn('slug', array_keys(self::SUPERSEDED))->update(['is_published' => false]);
        });
    }

    /** @return list<array> every experiment, checked before anything is written */
    public function load(): array
    {
        $all = [];

        foreach (self::FILES as $file) {
            foreach (require database_path("seeders/game/{$file}.php") as $data) {
                $this->check($data, $file);
                $all[$data['slug']] = $data;
            }
        }

        return array_values($all);
    }

    private function write(array $data): void
    {
        $experiment = Experiment::updateOrCreate(['slug' => $data['slug']], [
            'title' => $data['title'],
            'topic' => $data['topic'],
            'difficulty' => $data['difficulty'],
            'grade_level' => $data['grade_level'],
            'board' => $data['board'],
            'chemical_equation' => $data['chemical_equation'],
            'explanatory_note' => $data['explanatory_note'],
            'ar_available' => $data['ar'] ?? false,
            'base_points' => $data['base_points'],
            'estimated_minutes' => $data['estimated_minutes'],
            'emoji' => $data['emoji'],
            'card_gradient' => self::GRADIENTS[$data['grade_level']],
            'is_published' => true,
        ]);

        foreach ($data['steps'] as $step) {
            $experiment->steps()->updateOrCreate(['step_order' => $step['step_order']], [
                'kind' => $step['kind'],
                'title' => $step['title'],
                'instruction_text' => $step['instruction_text'],
                'preauthored_guidance' => $step['preauthored_guidance'],
                'config' => $step['config'] ?? [],
                'messages' => $step['messages'] ?? [],
                'action_label' => $step['action_label'] ?? null,
            ]);
        }

        // Content that lost a step since the last seed.
        $experiment->steps()->where('step_order', '>', count($data['steps']))->delete();

        foreach ($data['quiz'] as $question) {
            $experiment->quizQuestions()->updateOrCreate(['prompt' => $question['prompt']], $question);
        }
    }

    /** Refuses content that would break a rule, before a row is written. */
    private function check(array $data, string $file): void
    {
        $where = "{$file}.php, {$data['slug']}";

        foreach (array_values($data['steps']) as $i => $step) {
            if (($step['step_order'] ?? null) !== $i + 1) {
                throw new RuntimeException("{$where}: steps must be numbered 1, 2, 3 in order (FR-03.4).");
            }
            if (! in_array($step['kind'] ?? null, ExperimentStep::KINDS, true)) {
                throw new RuntimeException("{$where}: step {$i} has an unknown kind.");
            }
            if (blank($step['preauthored_guidance'] ?? null)) {
                throw new RuntimeException("{$where}: step ".($i + 1).' has no pre-authored guidance (NFR-06).');
            }
        }

        if (count($data['quiz']) < 1) {
            throw new RuntimeException("{$where}: an experiment needs a quiz (FR-04.1).");
        }

        foreach ($data['quiz'] as $q) {
            if (! isset($q['options'][$q['correct_option']])) {
                throw new RuntimeException("{$where}: a quiz answer points at an option that does not exist.");
            }
        }

        if (! in_array($data['grade_level'], [9, 10, 11, 12], true) || ! in_array($data['board'], ['punjab', 'federal', 'both'], true)) {
            throw new RuntimeException("{$where}: grade or board is outside the licence model.");
        }
    }
}
