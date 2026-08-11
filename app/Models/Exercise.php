<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use BelongsToOrganization;

    protected $table = 'exercise_library';

    protected $fillable = ['organization_id', 'owner_id', 'name', 'section', 'movement_type', 'instructions', 'default_sets', 'default_reps', 'default_load', 'unit', 'default_rest_seconds', 'media_url', 'is_shared', 'status'];

    protected function casts(): array
    {
        return ['is_shared' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(TrainingSessionExercise::class);
    }
}
