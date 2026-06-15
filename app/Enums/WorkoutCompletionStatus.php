<?php

namespace App\Enums;

enum WorkoutCompletionStatus: string
{
    case Completed = 'completed';
    case Partial = 'partial';
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Partial => 'Partial',
            self::Missed => 'Missed',
        };
    }
}
