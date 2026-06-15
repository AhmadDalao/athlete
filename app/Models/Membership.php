<?php

namespace App\Models;

use App\Enums\CoachAthleteStatus;
use App\Enums\MembershipStatus;
use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'renews_at',
        'ends_at',
        'grace_ends_at',
        'cancelled_at',
        'auto_renew',
        'price',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'starts_at' => 'date',
            'renews_at' => 'date',
            'ends_at' => 'date',
            'grace_ends_at' => 'date',
            'cancelled_at' => 'date',
            'auto_renew' => 'bool',
            'price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->hasRole(RoleName::Admin)) {
            return $query;
        }

        if ($viewer->hasRole(RoleName::Coach)) {
            $athleteIds = $viewer->coachAssignments()
                ->where('status', CoachAthleteStatus::Active->value)
                ->pluck('athlete_id')
                ->push($viewer->id)
                ->unique()
                ->values();

            return $query->whereIn('user_id', $athleteIds);
        }

        return $query->where('user_id', $viewer->id);
    }

    public function effectiveEndDate()
    {
        return $this->grace_ends_at ?? $this->ends_at ?? $this->renews_at;
    }

    public function daysRemaining(): ?int
    {
        $effectiveEndDate = $this->effectiveEndDate();

        if (! $effectiveEndDate) {
            return null;
        }

        return max(0, now()->startOfDay()->diffInDays($effectiveEndDate->copy()->startOfDay(), false));
    }
}
