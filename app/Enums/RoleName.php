<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Coach = 'coach';
    case Athlete = 'athlete';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Coach => 'Coach',
            self::Athlete => 'Athlete',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::Admin => 30,
            self::Coach => 20,
            self::Athlete => 10,
        };
    }

    /**
     * @return list<string>
     */
    public static function registrationValues(): array
    {
        return [
            self::Coach->value,
            self::Athlete->value,
        ];
    }
}
