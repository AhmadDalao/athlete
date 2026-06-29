import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="min-h-screen bg-[#fbfaf6] pb-24 md:pb-8">
                {children}

                <nav className="fixed inset-x-3 bottom-3 z-40 rounded-[1.5rem] border border-stone-200 bg-white/95 p-2 shadow-[0_18px_50px_-26px_rgba(15,23,42,0.45)] backdrop-blur md:hidden">
                    <div className="grid grid-cols-6 gap-1">
                        {navItems.map(({ key, label, href, icon: Icon, count }) => (
                            <Link
                                key={key}
                                href={href}
                                className={cn(
                                    'relative flex min-h-14 flex-col items-center justify-center gap-1 rounded-[1.05rem] px-1 text-[0.68rem] font-semibold text-stone-500 transition-colors',
                                    active === key && 'bg-emerald-700 text-white shadow-[0_14px_30px_-22px_rgba(4,120,87,0.9)]',
                                )}
                            >
                                <Icon className="size-4" />
                                <span>{label}</span>
                                {count > 0 && (
                                    <Badge className="absolute -top-1 -right-1 h-5 min-w-5 justify-center rounded-full bg-amber-300 px-1 text-[0.62rem] text-stone-950">
                                        {count}
                                    </Badge>
                                )}
                            </Link>
                        ))}
                    </div>
                </nav>
            </div>
        </AppLayout>
    );
}
