<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    protected $fillable = ['coach_id', 'athlete_id', 'title', 'goal', 'status', 'starts_on', 'ends_on', 'notes'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)->orderBy('scheduled_on');
    }

    /**
     * @return array{completed: int, total: int, percent: int}
     */
    public function completionStats(): array
    {
        $sessions = $this->sessions->where('status', '!=', 'cancelled');
        $total = $sessions->count();
        $completed = $sessions->filter(
            fn (TrainingSession $session): bool => $session->logs
                ->where('athlete_id', $this->athlete_id)
                ->contains('status', 'completed')
        )->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }
}
