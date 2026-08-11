<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $organizationId = app(OrganizationContext::class)->id();

            if ($organizationId) {
                $builder->where($builder->qualifyColumn('organization_id'), $organizationId);
            }
        });

        static::creating(function (self $model): void {
            if (! $model->organization_id) {
                $model->organization_id = app(OrganizationContext::class)->id();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where($query->qualifyColumn('organization_id'), $organizationId);
    }
}
