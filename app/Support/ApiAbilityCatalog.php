<?php

namespace App\Support;

use App\Enums\RoleName;
use App\Models\User;

class ApiAbilityCatalog
{
    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            'profile:read' => 'Read the authenticated user profile and token metadata.',
            'dashboard:read' => 'Read the role-aware dashboard summary.',
            'roster:read' => 'Read coach-athlete assignment data.',
            'training:read' => 'Read training programs, sessions, and workout logs.',
            'training:write' => 'Create or update athlete workout logs.',
            'progress:read' => 'Read athlete check-ins and progress analytics.',
            'progress:write' => 'Create or update athlete check-ins.',
            'membership:read' => 'Read visible memberships and recent payment history.',
            'wearable:read' => 'Read device connections, latest snapshots, and trend analytics.',
            'admin:read' => 'Read the admin control-center metrics and queues.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedFor(User $user): array
    {
        $abilities = [];

        if ($user->hasRole(RoleName::Admin)) {
            $abilities = array_merge($abilities, [
                'profile:read',
                'dashboard:read',
                'roster:read',
                'training:read',
                'progress:read',
                'membership:read',
                'wearable:read',
                'admin:read',
            ]);
        }

        if ($user->hasRole(RoleName::Coach)) {
            $abilities = array_merge($abilities, [
                'profile:read',
                'dashboard:read',
                'roster:read',
                'training:read',
                'progress:read',
                'membership:read',
                'wearable:read',
            ]);
        }

        if ($user->hasRole(RoleName::Athlete)) {
            $abilities = array_merge($abilities, [
                'profile:read',
                'dashboard:read',
                'training:read',
                'training:write',
                'progress:read',
                'progress:write',
                'membership:read',
                'wearable:read',
            ]);
        }

        return collect($abilities)->unique()->values()->all();
    }

    /**
     * @return list<array{name:string,description:string}>
     */
    public static function payloadFor(User $user): array
    {
        $descriptions = self::descriptions();

        return collect(self::allowedFor($user))
            ->map(fn (string $ability): array => [
                'name' => $ability,
                'description' => $descriptions[$ability] ?? 'Undocumented ability.',
            ])
            ->values()
            ->all();
    }
}
