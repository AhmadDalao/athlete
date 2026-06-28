import { type NavGroup, type NavItem, type User } from '@/types';
import {
    Bell,
    Cable,
    CreditCard,
    Dumbbell,
    Files,
    FileClock,
    LayoutGrid,
    LineChart,
    MailCheck,
    MailPlus,
    MessageCircle,
    Settings2,
    Shield,
    Smartphone,
    Users,
    Watch,
} from 'lucide-react';

export function buildMainNavGroups(user: User | null): NavGroup[] {
    const can = (permission: string) => user?.permissions?.includes(permission) ?? false;
    const isAdminLike = user?.primary_role === 'owner' || user?.primary_role === 'admin';

    if (isAdminLike) {
        return [
            {
                title: 'Overview',
                items: [
                    {
                        title: 'Dashboard',
                        url: '/dashboard',
                        icon: LayoutGrid,
                    },
                    ...(can('admin.control_center.view')
                        ? [
                              {
                                  title: 'Control center',
                                  url: '/admin/control-center',
                                  icon: Shield,
                              },
                          ]
                        : []),
                    ...(can('notifications.manage')
                        ? [
                              {
                                  title: 'Notifications',
                                  url: '/notifications',
                                  icon: Bell,
                              },
                          ]
                        : []),
                ],
            },
            {
                title: 'People',
                items: [
                    ...(can('roster.view')
                        ? [
                              {
                                  title: 'Roster',
                                  url: '/roster',
                                  icon: Users,
                              },
                          ]
                        : []),
                    ...(can('admin.invitations.view')
                        ? [
                              {
                                  title: 'Invitations',
                                  url: '/admin/invitations',
                                  icon: MailPlus,
                              },
                          ]
                        : []),
                    ...(can('admin.users.view')
                        ? [
                              {
                                  title: 'Users',
                                  url: '/admin/users',
                                  icon: Users,
                              },
                          ]
                        : []),
                ],
            },
            {
                title: 'Performance',
                items: [
                    ...(can('training.view')
                        ? [
                              {
                                  title: 'Training',
                                  url: '/training',
                                  icon: Dumbbell,
                              },
                          ]
                        : []),
                    ...(can('progress.view')
                        ? [
                              {
                                  title: 'Progress',
                                  url: '/progress',
                                  icon: LineChart,
                              },
                          ]
                        : []),
                    ...(can('wearables.view')
                        ? [
                              {
                                  title: 'Wearables',
                                  url: '/wearables',
                                  icon: Watch,
                              },
                          ]
                        : []),
                ],
            },
            {
                title: 'Commercial and API',
                items: [
                    ...(can('memberships.view')
                        ? [
                              {
                                  title: 'Memberships',
                                  url: '/memberships',
                                  icon: CreditCard,
                              },
                          ]
                        : []),
                    ...(can('api_access.manage')
                        ? [
                              {
                                  title: 'API access',
                                  url: '/api-access',
                                  icon: Cable,
                              },
                          ]
                        : []),
                    ...(can('admin.system_settings.manage')
                        ? [
                              {
                                  title: 'Website control',
                                  url: '/admin/system-settings',
                                  icon: Settings2,
                              },
                          ]
                        : []),
                    ...(can('athlete.files.view')
                        ? [
                              {
                                  title: 'Files',
                                  url: '/admin/files',
                                  icon: Files,
                              },
                          ]
                        : []),
                ],
            },
            {
                title: 'Admin logs',
                items: [
                    ...(can('admin.audit_log.view')
                        ? [
                              {
                                  title: 'Audit log',
                                  url: '/admin/audit-log',
                                  icon: FileClock,
                              },
                          ]
                        : []),
                    ...(can('admin.email_logs.view')
                        ? [
                              {
                                  title: 'Email logs',
                                  url: '/admin/email-logs',
                                  icon: MailCheck,
                              },
                          ]
                        : []),
                ],
            },
        ].filter((group) => group.items.length > 0);
    }

    if (user?.primary_role === 'coach') {
        return [
            {
                title: 'Overview',
                items: [
                    {
                        title: 'Dashboard',
                        url: '/dashboard',
                        icon: LayoutGrid,
                    },
                    {
                        title: 'Roster',
                        url: '/roster',
                        icon: Users,
                    },
                    ...(can('roster.invite')
                        ? [
                              {
                                  title: 'Invitations',
                                  url: '/roster/invites',
                                  icon: MailPlus,
                              },
                          ]
                        : []),
                    {
                        title: 'Notifications',
                        url: '/notifications',
                        icon: Bell,
                    },
                    {
                        title: 'Messages',
                        url: '/messages',
                        icon: MessageCircle,
                    },
                ],
            },
            {
                title: 'Athlete work',
                items: [
                    {
                        title: 'Training',
                        url: '/training',
                        icon: Dumbbell,
                    },
                    {
                        title: 'Progress',
                        url: '/progress',
                        icon: LineChart,
                    },
                    {
                        title: 'Wearables',
                        url: '/wearables',
                        icon: Watch,
                    },
                ],
            },
            {
                title: 'Operations',
                items: [
                    {
                        title: 'Memberships',
                        url: '/memberships',
                        icon: CreditCard,
                    },
                    {
                        title: 'API access',
                        url: '/api-access',
                        icon: Cable,
                    },
                ],
            },
        ];
    }

    return [
        {
            title: 'My workspace',
            items: [
                {
                    title: 'Athlete app',
                    url: '/app',
                    icon: Smartphone,
                },
                {
                    title: 'Analytics dashboard',
                    url: '/dashboard',
                    icon: LayoutGrid,
                },
                {
                    title: 'Training',
                    url: '/training',
                    icon: Dumbbell,
                },
                {
                    title: 'Progress',
                    url: '/progress',
                    icon: LineChart,
                },
                {
                    title: 'Wearables',
                    url: '/wearables',
                    icon: Watch,
                },
                {
                    title: 'Notifications',
                    url: '/notifications',
                    icon: Bell,
                },
                {
                    title: 'Messages',
                    url: '/messages',
                    icon: MessageCircle,
                },
            ],
        },
        {
            title: 'Access',
            items: [
                {
                    title: 'My membership',
                    url: '/memberships',
                    icon: CreditCard,
                },
                {
                    title: 'API access',
                    url: '/api-access',
                    icon: Cable,
                },
            ],
        },
    ];
}

export function buildMainNavItems(user: User | null): NavItem[] {
    return buildMainNavGroups(user).flatMap((group) => group.items);
}
