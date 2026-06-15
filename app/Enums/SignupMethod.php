<?php

namespace App\Enums;

enum SignupMethod: string
{
    case Email = 'email';
    case Google = 'google';
    case Apple = 'apple';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Google => 'Google',
            self::Apple => 'Apple',
            self::Phone => 'Phone',
        };
    }

    public function isOauthProvider(): bool
    {
        return match ($this) {
            self::Google, self::Apple => true,
            self::Email, self::Phone => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function oauthValues(): array
    {
        return [
            self::Google->value,
            self::Apple->value,
        ];
    }
}
