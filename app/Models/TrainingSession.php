<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    protected $fillable = [
        'training_program_id',
        'title',
        'focus',
        'scheduled_on',
        'status',
        'exercises',
        'coach_notes',
        'media_url',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'exercises' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function exerciseSummary(): string
    {
        $items = collect($this->exercises ?? []);

        if ($items->isEmpty()) {
            return 'No exercises yet';
        }

        return $items->take(2)->map(function (array $exercise): string {
            $sets = $exercise['sets'] ?? '-';
            $reps = $exercise['reps'] ?? '-';

            return trim(($exercise['name'] ?? 'Exercise').' '.$sets.'x'.$reps);
        })->implode(', ');
    }

    public function mediaCount(): int
    {
        return (filled($this->media_url) ? 1 : 0)
            + collect($this->exercises ?? [])->filter(
                fn (array $exercise): bool => filled($exercise['media_url'] ?? null)
            )->count();
    }

    public function hasMedia(): bool
    {
        return $this->mediaCount() > 0;
    }
}
