<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentStep extends Model
{
    use HasFactory;

    /** docs/experiment-engine.md: the nine step kinds. */
    public const KINDS = ['gear', 'place', 'choose', 'measure', 'add_until', 'heat', 'read', 'observe', 'confirm'];

    /**
     * The config keys that are answers. They are stripped from every copy sent to a
     * client (FR-02.5, SR-07): the server judges, never the browser.
     */
    public const PRIVATE_KEYS = [
        'gear' => ['required', 'forbidden'],
        'place' => ['answer'],
        'choose' => ['correct', 'wrong'],
        'measure' => ['target', 'tolerance'],
        'add_until' => ['tolerance'],
        'heat' => ['sequence', 'min_seconds'],
        'read' => ['value', 'value_from', 'tolerance'],
        'observe' => ['correct'],
        'confirm' => [],
    ];

    protected $fillable = [
        'experiment_id', 'step_order', 'kind', 'config', 'messages', 'title', 'instruction_text',
        'preauthored_guidance', 'item_emoji', 'action_label',
    ];

    protected function casts(): array
    {
        return ['step_order' => 'integer', 'config' => 'array', 'messages' => 'array'];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id');
    }

    public function isGame(): bool
    {
        return ($this->kind ?? 'confirm') !== 'confirm';
    }

    /** The config a client may see: the answers removed. */
    public function publicConfig(): array
    {
        $kind = $this->kind ?? 'confirm';

        return array_diff_key($this->config ?? [], array_flip(self::PRIVATE_KEYS[$kind] ?? []));
    }

    /** A line for the avatar; the pre-authored guidance stands in for a missing one (NFR-06). */
    public function message(string $key): string
    {
        return (string) ($this->messages[$key] ?? $this->preauthored_guidance);
    }
}
