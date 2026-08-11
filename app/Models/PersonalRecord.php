<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'athlete_id', 'workout_set_log_id', 'exercise_name', 'record_type', 'value', 'unit', 'achieved_on'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'achieved_on' => 'date'];
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
