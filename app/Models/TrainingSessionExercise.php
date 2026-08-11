<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSessionExercise extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'training_session_id', 'exercise_id', 'sort_order', 'section', 'superset_label', 'name', 'target_sets', 'target_reps', 'target_load', 'unit', 'rest_seconds', 'notes', 'media_url', 'movement_type', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
