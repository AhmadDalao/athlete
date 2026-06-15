<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case Grace = 'grace';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Trialing',
            self::Active => 'Active',
            self::Grace => 'Grace period',
            self::PastDue => 'Past due',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function isCurrentish(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::Grace, self::PastDue => true,
            self::Cancelled, self::Expired => false,
        };
    }

    public function needsAttention(): bool
    {
        return match ($this) {
            self::Grace, self::PastDue => true,
            self::Trialing, self::Active, self::Cancelled, self::Expired => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Trialing->value,
            self::Active->value,
            self::Grace->value,
        ];
    }
}
