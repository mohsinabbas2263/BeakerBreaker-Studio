<?php

namespace Tests\Concerns;

use App\Models\ExperimentQuizQuestion;
use App\Models\SafetyTierQuestion;
use Illuminate\Testing\TestResponse;

/**
 * Drives a safety tier or a quiz the way a real client does: fetch the questions,
 * read the shuffled options, answer by position, send back the attempt id.
 *
 * $choose receives the stored question and returns the stored option index to
 * pick, or null to leave the question unanswered.
 */
trait AnswersAttempts
{
    protected function answerSafetyTier(int $tier, ?callable $choose = null, array $extra = []): TestResponse
    {
        $start = $this->postJson("/api/safety/tiers/{$tier}/start")->assertOk()->json();

        $answers = $this->answersFor(
            $start['questions'],
            fn (int $id) => SafetyTierQuestion::findOrFail($id),
            $choose ?? fn ($q) => $q->correct_option,
        );

        return $this->postJson("/api/safety/tiers/{$tier}/submit", array_merge([
            'answers' => $answers,
            '_safety_session' => $start['_safety_session'],
        ], $extra));
    }

    protected function answerQuiz(int $experimentId, ?callable $choose = null, array $extra = []): TestResponse
    {
        $quiz = $this->getJson("/api/experiments/{$experimentId}/quiz")->assertOk()->json();

        $answers = $this->answersFor(
            $quiz['questions'],
            fn (int $id) => ExperimentQuizQuestion::findOrFail($id),
            $choose ?? fn ($q) => $q->correct_option,
        );

        return $this->postJson("/api/experiments/{$experimentId}/quiz", array_merge([
            'answers' => $answers,
            '_quiz_session' => $quiz['_quiz_session'],
        ], $extra));
    }

    /** Converts a stored option index into the position the client was shown. */
    private function answersFor(array $shown, callable $find, callable $choose): array
    {
        $answers = [];

        foreach ($shown as $question) {
            $stored = $find($question['id']);
            $pick = $choose($stored);

            if ($pick === null) {
                continue;
            }

            $answers[$question['id']] = array_search($stored->options[$pick], $question['options'], true);
        }

        return $answers;
    }
}
