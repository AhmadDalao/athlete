<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachProfile extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'title', 'specialties', 'certifications', 'years_experience'];

    protected function casts(): array
    {
        return ['specialties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
