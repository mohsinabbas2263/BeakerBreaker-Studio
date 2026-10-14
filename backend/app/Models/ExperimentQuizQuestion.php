<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['experiment_id', 'prompt', 'options', 'correct_option', 'explanation'];

    /**
     * SR-07 and FR-02.5: correct_option and explanation never leave the server
     * in a question listing. QuizResource decides what a student may see.
     */
    protected $hidden = ['correct_option'];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_option' => 'integer'];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }
}
