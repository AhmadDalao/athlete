<?php

namespace App\Support;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'Admin' => [
                'admin.access' => 'Access admin backend',
                'admin.settings' => 'Manage website and system settings',
                'admin.permissions' => 'Manage admin permissions',
                'admin.audit' => 'View audit and email logs',
                'admin.contacts' => 'Manage website contact submissions',
            ],
            'People' => [
                'users.manage' => 'Create and edit users',
                'coaches.manage' => 'Create and edit coaches',
                'athletes.manage' => 'Create and edit athletes',
                'invitations.manage' => 'Manage athlete invitations',
            ],
            'Coaching' => [
                'coach.access' => 'Access coach workspace',
                'programs.manage' => 'Create programs and sessions',
                'athletes.view' => 'View assigned athlete records',
            ],
            'Athlete' => [
                'athlete.access' => 'Access athlete app',
                'progress.manage' => 'Log own progress',
                'workouts.complete' => 'Complete assigned workouts',
            ],
        ];
    }

    public static function all(): array
    {
        return collect(static::groups())->flatMap(fn (array $permissions) => array_keys($permissions))->values()->all();
    }

    public static function defaultsForRole(string $role): array
    {
        return match ($role) {
            'admin' => ['admin.access', 'admin.audit', 'admin.contacts', 'users.manage', 'coaches.manage', 'athletes.manage', 'invitations.manage'],
            'coach' => ['coach.access', 'programs.manage', 'athletes.view', 'invitations.manage'],
            'athlete' => ['athlete.access', 'progress.manage', 'workouts.complete'],
            default => static::all(),
        };
    }
}
