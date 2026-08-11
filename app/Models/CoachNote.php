<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachNote extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'coach_id', 'athlete_id', 'body', 'visibility', 'is_pinned'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean'];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
