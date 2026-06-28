<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneAuthChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'intent',
        'phone',
        'email',
        'name',
        'account_type',
        'preferred_contact_method',
        'delivery_driver',
        'code_hash',
        'attempts',
        'sent_at',
        'expires_at',
        'consumed_at',
        'primary_goal',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
