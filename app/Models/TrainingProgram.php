<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'coach_id', 'athlete_id', 'source_program_id', 'title', 'goal', 'status', 'starts_on', 'ends_on', 'notes', 'is_template', 'visibility', 'estimated_weeks'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_template' => 'boolean',
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

    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_program_id');
    }

    public function derivatives(): HasMany
    {
        return $this->hasMany(self::class, 'source_program_id');
    }

    public function athletePlans(): HasMany
    {
        return $this->derivatives()->where('is_template', false);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)->orderBy('scheduled_on');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProgramPhase::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProgramAssignment::class)->latest('starts_on');
    }

    /**
     * @return array{completed: int, total: int, percent: int}
     */
    public function completionStats(): array
    {
        if ($this->is_template) {
            $this->loadMissing('athletePlans.assignments.scheduledWorkouts.logs');
            $assignments = $this->athletePlans->flatMap->assignments;
        } else {
            $this->loadMissing('assignments.scheduledWorkouts.logs');
            $assignments = $this->assignments;
        }

        $logs = $assignments
            ->flatMap->scheduledWorkouts
            ->flatMap->logs;
        $total = $assignments->flatMap->scheduledWorkouts->count();
        $completed = $logs->where('status', 'completed')->count();

        if ($total === 0 && $this->athlete_id) {
            $sessions = $this->sessions->where('status', '!=', 'cancelled');
            $total = $sessions->count();
            $completed = $sessions->filter(
                fn (TrainingSession $session): bool => $session->logs
                    ->where('athlete_id', $this->athlete_id)
                    ->contains('status', 'completed')
            )->count();
        }

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }
}
