<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressPhoto extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'athlete_id', 'progress_entry_id', 'uploaded_by', 'path', 'thumbnail_path', 'category', 'visibility', 'taken_on', 'notes'];

    protected function casts(): array
    {
        return ['taken_on' => 'date'];
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
