import { UserAppShell } from '@/components/user-app-shell';
import { type BreadcrumbItem } from '@/types';
import { CalendarDays, HeartPulse, LayoutDashboard, MessageCircle, UserRound, Watch } from 'lucide-react';
import { type ReactNode } from 'react';

interface AthleteAppShellProps {
    children: ReactNode;
    active: 'app' | 'programs' | 'progress' | 'wearables' | 'messages' | 'profile';
    unreadMessages?: number;
    unreadNotifications?: number;
    breadcrumbs?: BreadcrumbItem[];
}

const defaultBreadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Athlete app',
        href: '/app',
    },
];

export function AthleteAppShell({
    children,
    active,
    unreadMessages = 0,
    breadcrumbs = defaultBreadcrumbs,
}: AthleteAppShellProps) {
    const navItems = [
        { key: 'app', label: 'App', href: '/app', icon: LayoutDashboard, count: 0 },
        { key: 'programs', label: 'Programs', href: '/app#programs', icon: CalendarDays, count: 0 },
        { key: 'progress', label: 'Progress', href: '/progress', icon: HeartPulse, count: 0 },
        { key: 'wearables', label: 'Devices', href: '/wearables', icon: Watch, count: 0 },
        { key: 'messages', label: 'Messages', href: '/messages', icon: MessageCircle, count: unreadMessages },
        { key: 'profile', label: 'Profile', href: '/settings/profile', icon: UserRound, count: 0 },
    ] as const;

    return (
        <UserAppShell
            active={active}
            navItems={navItems}
            eyebrow={breadcrumbs.at(-1)?.title ?? 'Athlete app'}
            title="Throughline"
            description="Your programs, calendar, workouts, coach messages, recovery trends, and membership live here. The admin dashboard stays out of the way."
            accentClassName="bg-emerald-700"
        >
            {children}
        </UserAppShell>
    );
}
