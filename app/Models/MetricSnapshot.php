<?php

namespace App\Models;

use App\Enums\DeviceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_connection_id',
        'provider',
        'metric_date',
        'readiness_score',
        'strain_score',
        'sleep_minutes',
        'sleep_need_minutes',
        'sleep_performance_percentage',
        'sleep_consistency_percentage',
        'sleep_efficiency_percentage',
        'rem_sleep_minutes',
        'slow_wave_sleep_minutes',
        'steps',
        'distance_meters',
        'calories_burned',
        'active_minutes',
        'resting_heart_rate',
        'heart_rate_variability',
        'respiratory_rate',
        'blood_oxygen_percent',
        'skin_temperature_celsius',
        'training_load',
    ];

    protected function casts(): array
    {
        return [
            'provider' => DeviceProvider::class,
            'metric_date' => 'date',
            'readiness_score' => 'float',
            'strain_score' => 'float',
            'sleep_minutes' => 'integer',
            'sleep_need_minutes' => 'integer',
            'sleep_performance_percentage' => 'float',
            'sleep_consistency_percentage' => 'float',
            'sleep_efficiency_percentage' => 'float',
            'rem_sleep_minutes' => 'integer',
            'slow_wave_sleep_minutes' => 'integer',
            'steps' => 'integer',
            'distance_meters' => 'integer',
            'calories_burned' => 'integer',
            'active_minutes' => 'integer',
            'resting_heart_rate' => 'integer',
            'heart_rate_variability' => 'float',
            'respiratory_rate' => 'float',
            'blood_oxygen_percent' => 'float',
            'skin_temperature_celsius' => 'float',
            'training_load' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deviceConnection(): BelongsTo
    {
        return $this->belongsTo(DeviceConnection::class);
    }

    public function sleepHours(): ?float
    {
        if ($this->sleep_minutes === null) {
            return null;
        }

        return round($this->sleep_minutes / 60, 1);
    }

    public function sleepNeedHours(): ?float
    {
        if ($this->sleep_need_minutes === null) {
            return null;
        }

        return round($this->sleep_need_minutes / 60, 1);
    }

    public function sleepDebtHours(): ?float
    {
        if ($this->sleep_need_minutes === null || $this->sleep_minutes === null) {
            return null;
        }

        return round(($this->sleep_need_minutes - $this->sleep_minutes) / 60, 1);
    }

    public function readinessBand(): ?string
    {
        if ($this->readiness_score === null) {
            return null;
        }

        return match (true) {
            $this->readiness_score >= 80 => 'green',
            $this->readiness_score >= 67 => 'yellow',
            default => 'red',
        };
    }
}
