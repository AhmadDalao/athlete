<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteProfile extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'date_of_birth', 'gender', 'height_cm', 'sport', 'position', 'timezone', 'emergency_contact_name', 'emergency_contact_phone', 'metadata'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'height_cm' => 'decimal:2', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
