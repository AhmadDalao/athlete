<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_id',
        'title',
        'scheduled_date',
        'focus',
        'instructions',
        'exercises',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'exercises' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function workoutLog(): HasOne
    {
        return $this->hasOne(WorkoutLog::class);
    }
}
