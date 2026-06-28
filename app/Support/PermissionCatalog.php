<?php

namespace App\Support;

use App\Enums\RoleName;

class PermissionCatalog
{
    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'overview' => [
                'label' => 'Overview',
                'permissions' => [
                    'dashboard.view' => 'Open the dashboard.',
                    'admin.control_center.view' => 'Open the admin control center.',
                    'notifications.manage' => 'Create system notifications.',
                ],
            ],
            'people' => [
                'label' => 'People',
                'permissions' => [
                    'roster.view' => 'View roster assignments.',
                    'roster.manage' => 'Create and update coach-athlete assignments.',
                    'roster.invite' => 'Invite athletes into a coach-owned roster.',
                    'athletes.view' => 'Open athlete profiles with schedule, progress, and training history.',
                    'athlete.files.view' => 'View athlete file-library records and downloads.',
                    'athlete.files.manage' => 'Upload, move, archive, and manage athlete files.',
                    'admin.users.view' => 'View admin user lists and profiles.',
                    'admin.users.manage' => 'Create users and edit account roles, permissions, and onboarding fields.',
                    'admin.invitations.view' => 'View all athlete invitations across the platform.',
                    'admin.invitations.manage' => 'Resend, cancel, and manage all athlete invitations.',
                ],
            ],
            'performance' => [
                'label' => 'Performance',
                'permissions' => [
                    'training.view' => 'View training programs and sessions.',
                    'training.manage' => 'Create programs, assign sessions, and manage workout plans.',
                    'progress.view' => 'View progress, food, body, hydration, and check-in data.',
                    'progress.manage' => 'Create and update athlete progress check-ins.',
                    'wearables.view' => 'View wearable connections and recovery metrics.',
                    'wearables.manage' => 'Manage device review state and wearable integration details.',
                ],
            ],
            'commercial' => [
                'label' => 'Commercial and API',
                'permissions' => [
                    'memberships.view' => 'View memberships, billing status, and payment activity.',
                    'memberships.manage' => 'Update membership state and record payment events.',
                    'api_access.manage' => 'Create API tokens and view integration handoff details.',
                ],
            ],
            'system' => [
                'label' => 'System',
                'permissions' => [
                    'admin.system_settings.manage' => 'Manage Website Control and system settings.',
                    'admin.audit_log.view' => 'View and export audit logs.',
                    'admin.email_logs.view' => 'View and export email delivery logs.',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function defaultsForRole(RoleName|string $role): array
    {
        $roleName = $role instanceof RoleName ? $role : RoleName::from($role);

        if ($roleName === RoleName::Owner || $roleName === RoleName::Admin) {
            return self::keys();
        }

        if ($roleName === RoleName::Coach) {
            return [
                'dashboard.view',
                'notifications.manage',
                'roster.view',
                'roster.manage',
                'roster.invite',
                'athletes.view',
                'athlete.files.view',
                'athlete.files.manage',
                'training.view',
                'training.manage',
                'progress.view',
                'progress.manage',
                'wearables.view',
                'memberships.view',
                'api_access.manage',
            ];
        }

        return [
            'dashboard.view',
            'training.view',
            'training.manage',
            'progress.view',
            'progress.manage',
            'wearables.view',
            'memberships.view',
            'api_access.manage',
        ];
    }

    /**
     * @param  array<int, string>  $roles
     * @return list<string>
     */
    public static function defaultsForRoles(array $roles): array
    {
        return collect($roles)
            ->flatMap(fn (string $role) => self::defaultsForRole($role))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return list<string>
     */
    public static function sanitize(array $permissions): array
    {
        $valid = array_fill_keys(self::keys(), true);

        return collect($permissions)
            ->filter(fn ($permission) => is_string($permission) && isset($valid[$permission]))
            ->unique()
            ->values()
            ->all();
    }
}
