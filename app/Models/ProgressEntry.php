<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressEntry extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'athlete_id',
        'logged_on',
        'weight',
        'calories',
        'protein',
        'hydration',
        'sleep_quality',
        'soreness',
        'energy',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'logged_on' => 'date',
            'weight' => 'decimal:2',
        ];
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
