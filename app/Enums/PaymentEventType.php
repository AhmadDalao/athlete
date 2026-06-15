<?php

namespace App\Enums;

enum PaymentEventType: string
{
    case Invoice = 'invoice';
    case Charge = 'charge';
    case Refund = 'refund';
    case MembershipChange = 'membership_change';
    case ManualAdjustment = 'manual_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Invoice',
            self::Charge => 'Charge',
            self::Refund => 'Refund',
            self::MembershipChange => 'Membership change',
            self::ManualAdjustment => 'Manual adjustment',
        };
    }
}
