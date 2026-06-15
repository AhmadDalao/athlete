<?php

namespace App\Enums;

enum DeviceConnectionStatus: string
{
    case Connected = 'connected';
    case Attention = 'attention';
    case Disconnected = 'disconnected';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::Attention => 'Needs attention',
            self::Disconnected => 'Disconnected',
        };
    }
}
