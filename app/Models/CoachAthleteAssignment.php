<?php

namespace App\Models;

use App\Enums\CoachAthleteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachAthleteAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'athlete_id',
        'status',
        'goal',
        'notes',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CoachAthleteStatus::class,
            'started_at' => 'date',
            'ended_at' => 'date',
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

    public function syncLifecycleDates(): void
    {
        if (! $this->started_at) {
            $this->started_at = now()->toDateString();
        }

        if ($this->status === CoachAthleteStatus::Archived) {
            $this->ended_at = $this->ended_at ?? now()->toDateString();

            return;
        }

        $this->ended_at = null;
    }
}
