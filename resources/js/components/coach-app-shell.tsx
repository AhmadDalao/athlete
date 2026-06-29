import { UserAppShell } from '@/components/user-app-shell';
import { CalendarDays, Dumbbell, LineChart, MailPlus, MessageCircle, Smartphone, Users } from 'lucide-react';
import { type ReactNode } from 'react';

interface CoachAppShellProps {
    children: ReactNode;
    active: 'home' | 'roster' | 'programs' | 'schedule' | 'messages' | 'invites' | 'progress';
    unreadMessages?: number;
}

export function CoachAppShell({ children, active, unreadMessages = 0 }: CoachAppShellProps) {
    const navItems = [
        { key: 'home', label: 'Home', href: '/coach', icon: Smartphone, count: 0 },
        { key: 'roster', label: 'Roster', href: '/roster', icon: Users, count: 0 },
        { key: 'programs', label: 'Programs', href: '/training', icon: Dumbbell, count: 0 },
        { key: 'schedule', label: 'Schedule', href: '/coach#schedule', icon: CalendarDays, count: 0 },
        { key: 'messages', label: 'Messages', href: '/messages', icon: MessageCircle, count: unreadMessages },
        { key: 'invites', label: 'Invites', href: '/roster/invites', icon: MailPlus, count: 0 },
        { key: 'progress', label: 'Progress', href: '/progress', icon: LineChart, count: 0 },
    ] as const;

    return (
        <UserAppShell
            active={active}
            navItems={navItems}
            eyebrow="Coach app"
            title="Throughline"
            description="Coach-facing workspace for assigned athletes, today’s schedule, program follow-through, invites, and messages."
            accentClassName="bg-amber-500"
        >
            {children}
        </UserAppShell>
    );
}
