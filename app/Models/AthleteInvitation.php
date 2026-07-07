<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteInvitation extends Model
{
    protected $fillable = [
        'coach_id',
        'email',
        'name',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }
}
