<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteCheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'logged_date',
        'weight_kg',
        'body_fat_percentage',
        'waist_cm',
        'calories_consumed',
        'protein_grams',
        'carbs_grams',
        'fat_grams',
        'water_liters',
        'meals_logged_count',
        'energy_score',
        'soreness_score',
        'stress_score',
        'sleep_quality_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'logged_date' => 'date',
            'weight_kg' => 'float',
            'body_fat_percentage' => 'float',
            'waist_cm' => 'float',
            'calories_consumed' => 'integer',
            'protein_grams' => 'integer',
            'carbs_grams' => 'integer',
            'fat_grams' => 'integer',
            'water_liters' => 'float',
            'meals_logged_count' => 'integer',
            'energy_score' => 'integer',
            'soreness_score' => 'integer',
            'stress_score' => 'integer',
            'sleep_quality_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
