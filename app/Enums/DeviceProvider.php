<?php

namespace App\Enums;

enum DeviceProvider: string
{
    case Garmin = 'garmin';
    case Strava = 'strava';
    case Whoop = 'whoop';
    case Oura = 'oura';
    case AppleHealth = 'apple_health';
    case HealthConnect = 'health_connect';

    public function label(): string
    {
        return match ($this) {
            self::Garmin => 'Garmin',
            self::Strava => 'Strava',
            self::Whoop => 'Whoop',
            self::Oura => 'Oura',
            self::AppleHealth => 'Apple Health',
            self::HealthConnect => 'Health Connect',
        };
    }
}
