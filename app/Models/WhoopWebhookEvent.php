<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhoopWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_connection_id',
        'trace_id',
        'whoop_user_id',
        'resource_id',
        'event_type',
        'processing_status',
        'attempts',
        'last_error_message',
        'received_at',
        'processed_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function deviceConnection(): BelongsTo
    {
        return $this->belongsTo(DeviceConnection::class);
    }
}
