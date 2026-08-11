<?php

namespace App\Support;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'Platform' => [
                'admin.access' => 'Access admin backend',
                'admin.settings' => 'Manage website and system settings',
                'admin.permissions' => 'Manage admin permissions',
                'admin.audit' => 'View audit and email logs',
                'admin.contacts' => 'Manage website contact submissions',
                'organizations.manage' => 'Create and manage organizations',
            ],
            'People' => [
                'users.manage' => 'Create and edit users',
                'coaches.manage' => 'Create and edit coaches',
                'athletes.manage' => 'Create and edit athletes',
                'invitations.manage' => 'Manage athlete invitations',
                'roster.assign' => 'Assign coaches and athletes',
            ],
            'Coaching' => [
                'coach.access' => 'Access coach workspace',
                'programs.manage' => 'Create programs and sessions',
                'programs.assign' => 'Assign programs to athletes',
                'schedule.manage' => 'Schedule and reschedule workouts',
                'exercises.manage' => 'Manage the exercise library',
                'athletes.view' => 'View assigned athlete records',
                'athletes.notes' => 'Manage private coach notes',
                'progress.review' => 'Review athlete progress and photos',
                'reports.view' => 'View coaching reports',
            ],
            'Athlete' => [
                'athlete.access' => 'Access athlete app',
                'progress.manage' => 'Log own progress',
                'workouts.complete' => 'Complete assigned workouts',
                'photos.manage' => 'Manage own progress photos',
            ],
            'Communication' => [
                'messages.read' => 'Read permitted conversations',
                'messages.send' => 'Send messages in permitted conversations',
                'notifications.read' => 'Read own notifications',
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
            'admin' => ['admin.access', 'admin.audit', 'admin.contacts', 'users.manage', 'coaches.manage', 'athletes.manage', 'invitations.manage', 'reports.view'],
            'coach' => static::defaultsForOrganizationRole('coach'),
            'athlete' => static::defaultsForOrganizationRole('athlete'),
            default => static::all(),
        };
    }

    public static function defaultsForOrganizationRole(string $role): array
    {
        return match ($role) {
            'organization_owner' => collect(static::all())->reject(fn (string $permission): bool => static::isPlatformOnly($permission))->values()->all(),
            'organization_admin' => [
                'admin.access', 'users.manage', 'coaches.manage', 'athletes.manage', 'invitations.manage',
                'roster.assign', 'programs.manage', 'programs.assign', 'schedule.manage', 'exercises.manage',
                'athletes.view', 'athletes.notes', 'progress.review', 'reports.view', 'messages.read',
                'messages.send', 'notifications.read',
            ],
            'coach' => [
                'coach.access', 'programs.manage', 'programs.assign', 'schedule.manage', 'exercises.manage',
                'athletes.view', 'athletes.notes', 'progress.review', 'invitations.manage', 'reports.view',
                'messages.read', 'messages.send', 'notifications.read',
            ],
            'athlete' => [
                'athlete.access', 'progress.manage', 'workouts.complete', 'photos.manage',
                'messages.read', 'messages.send', 'notifications.read',
            ],
            default => [],
        };
    }

    public static function isPlatformOnly(string $permission): bool
    {
        return in_array($permission, [
            'admin.settings',
            'admin.permissions',
            'admin.audit',
            'admin.contacts',
            'organizations.manage',
        ], true);
    }
}
