<?php

namespace App\Models;

use App\Enums\PaymentEventStatus;
use App\Enums\PaymentEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_id',
        'user_id',
        'created_by_user_id',
        'event_type',
        'status',
        'provider',
        'reference',
        'amount',
        'currency',
        'event_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => PaymentEventType::class,
            'status' => PaymentEventStatus::class,
            'amount' => 'decimal:2',
            'event_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
