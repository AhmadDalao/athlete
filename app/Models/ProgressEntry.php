<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressEntry extends Model
{
    protected $fillable = [
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
