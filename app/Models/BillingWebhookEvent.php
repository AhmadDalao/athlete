<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'livemode',
        'processed_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'livemode' => 'bool',
            'processed_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}
