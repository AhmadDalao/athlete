<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'mailer',
        'mailable',
        'message_id',
        'recipient',
        'subject',
        'source',
        'error',
        'metadata',
        'attempted_at',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'attempted_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
