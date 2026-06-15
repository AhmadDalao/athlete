<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMetricIngest extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_connection_id',
        'metric_date',
        'external_event_id',
        'payload',
        'processing_status',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function deviceConnection(): BelongsTo
    {
        return $this->belongsTo(DeviceConnection::class);
    }
}
