import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type AuthenticatedSharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

export interface UserAppNavItem {
    key: string;
    label: string;
    href: string;
    icon: LucideIcon;
    count?: number;
}

interface UserAppShellProps {
    children: ReactNode;
    active: string;
    navItems: readonly UserAppNavItem[];
    eyebrow: string;
    title: string;
    description: string;
    accentClassName?: string;
}

export function UserAppShell({
    children,
    active,
    navItems,
    eyebrow,
    title,
    description,
    accentClassName = 'bg-emerald-700',
}: UserAppShellProps) {
    const { auth, notifications } = usePage<AuthenticatedSharedData>().props;
    const initials = useInitials();
    const unreadNotifications = notifications?.unreadCount ?? 0;

    return (
        <div className="min-h-screen overflow-x-hidden bg-[#fbfaf6] pb-[calc(6.75rem+env(safe-area-inset-bottom))] text-stone-950 md:pb-10">
            <header className="sticky top-0 z-40 border-b border-stone-200/80 bg-[#fbfaf6]/95 backdrop-blur-xl md:static md:border-b-0 md:bg-transparent">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-2 px-3 py-2.5 md:gap-3 md:px-6 md:py-5">
                    <Link href={auth.user.landing_path ?? '/app'} className="flex min-w-0 items-center gap-3">
                        <span className={cn('grid size-10 shrink-0 place-items-center rounded-2xl text-white shadow-[0_18px_38px_-28px_rgba(15,23,42,0.55)] md:size-11', accentClassName)}>
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        <span className="min-w-0">
                            <span className="block truncate text-[0.64rem] font-semibold tracking-[0.18em] text-stone-400 uppercase md:text-xs">{eyebrow}</span>
                            <span className="block truncate font-['Space_Grotesk'] text-base font-bold tracking-[-0.04em] text-stone-950 md:text-lg">{title}</span>
                        </span>
                    </Link>

                    <nav className="hidden items-center gap-1 rounded-full border border-stone-200 bg-white p-1 shadow-[0_14px_36px_-30px_rgba(15,23,42,0.35)] lg:flex">
                        {navItems.map(({ key, label, href, icon: Icon, count = 0 }) => (
                            <Link
                                key={key}
                                href={href}
                                className={cn(
                                    'relative flex h-10 items-center gap-2 rounded-full px-4 text-sm font-semibold text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-950',
                                    active === key && 'bg-stone-950 text-white hover:bg-stone-900 hover:text-white',
                                )}
                            >
                                <Icon className="size-4" />
                                {label}
                                {count > 0 && (
                                    <span className="grid min-w-5 place-items-center rounded-full bg-amber-300 px-1.5 text-[0.68rem] text-stone-950">{count}</span>
                                )}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" size="icon" className="relative size-9 rounded-full border-stone-200 bg-white md:size-10">
                            <Link href={route('notifications.index')} aria-label="Notifications">
                                <Bell className="size-4" />
                                {unreadNotifications > 0 && (
                                    <span className="absolute -top-1 -right-1 grid min-w-5 place-items-center rounded-full bg-amber-300 px-1 text-[0.65rem] font-bold text-stone-950">
                                        {unreadNotifications}
                                    </span>
                                )}
                            </Link>
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    className="grid size-9 place-items-center rounded-full border border-stone-200 bg-white text-sm font-bold text-emerald-900 shadow-[0_12px_26px_-22px_rgba(15,23,42,0.45)] md:size-10"
                                    aria-label="Account menu"
                                >
                                    {initials(auth.user.name)}
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="mx-auto hidden max-w-6xl px-6 pb-4 md:block lg:hidden">
                    <div className="flex gap-2 overflow-x-auto rounded-[1.35rem] border border-stone-200 bg-white p-2 shadow-[0_14px_36px_-32px_rgba(15,23,42,0.35)]">
                        {navItems.map(({ key, label, href, icon: Icon, count = 0 }) => (
                            <Link
                                key={key}
                                href={href}
                                className={cn(
                                    'relative flex h-11 shrink-0 items-center gap-2 rounded-2xl px-4 text-sm font-semibold text-stone-600',
                                    active === key && 'bg-stone-950 text-white',
                                )}
                            >
                                <Icon className="size-4" />
                                {label}
                                {count > 0 && <Badge className="bg-amber-300 text-stone-950 hover:bg-amber-300">{count}</Badge>}
                            </Link>
                        ))}
                    </div>
                </div>
            </header>

            <section className="mx-auto hidden max-w-6xl px-4 pt-4 md:block md:px-6 md:pt-0">
                <div className="rounded-[1.75rem] border border-stone-200 bg-white p-4 shadow-[0_20px_50px_-42px_rgba(15,23,42,0.38)] md:rounded-[2rem] md:p-5">
                    <p className="text-xs font-semibold tracking-[0.2em] text-stone-400 uppercase">{eyebrow}</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">{description}</p>
                </div>
            </section>

            <main className="min-w-0 overflow-x-hidden">{children}</main>

            <nav className="fixed inset-x-3 bottom-3 z-40 rounded-[1.5rem] border border-stone-200 bg-white/95 p-2 shadow-[0_18px_50px_-26px_rgba(15,23,42,0.45)] backdrop-blur md:hidden">
                <div className="grid grid-cols-5 gap-1">
                    {navItems.slice(0, 5).map(({ key, label, href, icon: Icon, count = 0 }) => (
                        <Link
                            key={key}
                            href={href}
                            className={cn(
                                'relative flex min-h-14 flex-col items-center justify-center gap-1 rounded-[1.05rem] px-1 text-[0.66rem] font-semibold text-stone-500 transition-colors',
                                active === key && cn('text-white shadow-[0_14px_30px_-22px_rgba(4,120,87,0.9)]', accentClassName),
                            )}
                        >
                            <Icon className="size-4" />
                            <span className="max-w-full truncate">{label}</span>
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
    );
}
