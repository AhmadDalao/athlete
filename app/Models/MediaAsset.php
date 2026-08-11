<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAsset extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'uploaded_by', 'attachable_type', 'attachable_id', 'type', 'disk', 'path', 'url', 'mime_type', 'original_name', 'size', 'caption', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
