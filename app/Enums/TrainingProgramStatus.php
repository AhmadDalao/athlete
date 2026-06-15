<?php

namespace App\Enums;

enum TrainingProgramStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    public function isCurrent(): bool
    {
        return match ($this) {
            self::Draft, self::Active => true,
            self::Completed, self::Archived => false,
        };
    }
}
