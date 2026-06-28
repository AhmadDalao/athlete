<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteFile extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const VISIBILITY_COACH_ADMIN = 'coach_admin';
    public const VISIBILITY_ATHLETE = 'athlete_visible';
    public const VISIBILITY_ADMIN = 'admin_only';

    protected $fillable = [
        'athlete_id',
        'uploaded_by_user_id',
        'archived_by_user_id',
        'category',
        'visibility',
        'status',
        'display_name',
        'original_filename',
        'stored_path',
        'mime_type',
        'size_bytes',
        'notes',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }
}
