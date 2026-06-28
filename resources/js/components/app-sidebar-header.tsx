import { Breadcrumbs } from '@/components/breadcrumbs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { type AuthenticatedSharedData, type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell, Search, Settings2 } from 'lucide-react';

const descriptions: Record<string, string> = {
    Dashboard: 'The fastest route to what matters today.',
    'Control center': 'Platform-wide operations without the scavenger hunt.',
    Roster: 'Coach-athlete assignments, gaps, and ownership in one place.',
    Training: 'Programs, sessions, sets, reps, and log follow-through.',
    Progress: 'Weight, nutrition, hydration, soreness, and trend context.',
    Wearables: 'Device health, WHOOP syncs, recovery, and webhook readiness.',
    Memberships: 'Billing state, remaining access time, and renewal control.',
    'API access': 'Keys, tokens, webhooks, and integration handoff.',
    Contact: 'Reach support without leaving the product.',
    Notifications: 'System messages, admin broadcasts, and read state.',
    Search: 'Find users, athletes, memberships, training, wearables, and notices fast.',
    'Audit log': 'Who changed what, when, and from where.',
    'Email logs': 'Password reset, verification, and workflow email delivery trail.',
    'System settings': 'Mail identity, public text, notices, and platform controls.',
    'Website control': 'Editable public copy, mail identity, and operator-safe settings.',
    Users: 'Role control, onboarding fields, and account status.',
    Settings: 'Profile and account-level changes.',
};

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth, notifications } = usePage<AuthenticatedSharedData>().props;
    const currentPage = breadcrumbs.at(-1)?.title ?? 'Dashboard';
    const unreadCount = notifications?.unreadCount ?? 0;

    return (
        <header className="flex shrink-0 items-center gap-3 pt-4 pb-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:pt-3">
            <div className="grid w-full gap-4 rounded-[1.6rem] border border-stone-200/90 bg-white px-5 py-4 shadow-[0_16px_32px_-28px_rgba(15,23,42,0.18)] lg:grid-cols-[1fr_auto] lg:items-center">
                <div className="min-w-0 space-y-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <SidebarTrigger className="-ml-1 rounded-full border border-stone-200 bg-white text-stone-700 hover:bg-stone-100 hover:text-stone-950" />
                        <Badge variant="outline" className="border-stone-200 bg-stone-50 text-stone-700">
                            {auth.user.primary_role ?? 'user'}
                        </Badge>
                        <p className="text-sm font-medium text-stone-900">{currentPage}</p>
                    </div>
                    <div className="space-y-1">
                        <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-400 uppercase">Workspace focus</p>
                        <p className="max-w-3xl text-sm leading-7 text-stone-600">{descriptions[currentPage] ?? 'Keep the next decision obvious.'}</p>
                    </div>
                    <div className="text-sm text-stone-500">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>

                <div className="flex min-w-0 flex-wrap items-center gap-3 lg:justify-end">
                    <form action={route('search.index')} method="get" className="min-w-[220px] flex-1 lg:max-w-[360px]">
                        <label className="relative block">
                            <span className="sr-only">Search workspace</span>
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                            <input
                                name="q"
                                type="search"
                                placeholder="Search workspace..."
                                className="h-11 w-full rounded-full border border-stone-200 bg-white pr-4 pl-10 text-sm text-stone-900 transition outline-none placeholder:text-stone-400 focus:border-stone-400 focus:ring-2 focus:ring-amber-100"
                            />
                        </label>
                    </form>
                    <div className="hidden rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 md:flex md:min-w-[240px]">
                        <UserInfo user={auth.user} />
                    </div>
                    <Button asChild size="sm" variant="outline" className="rounded-xl border-stone-200 bg-white">
                        <Link href={route('contact.show')}>Need help?</Link>
                    </Button>
                    <Button asChild size="sm" variant="outline" className="rounded-xl border-stone-200 bg-white">
                        <Link href={route('notifications.index')} className="relative">
                            <Bell className="size-4" />
                            Alerts
                            {unreadCount > 0 && (
                                <span className="ml-1 rounded-full bg-amber-200 px-2 py-0.5 text-xs font-semibold text-stone-950">{unreadCount}</span>
                            )}
                        </Link>
                    </Button>
                    <Button asChild size="sm" className="rounded-xl bg-stone-950 text-white hover:bg-stone-800">
                        <Link href={route('profile.edit')}>
                            <Settings2 className="size-4" />
                            Account
                        </Link>
                    </Button>
                </div>
            </div>
        </header>
    );
}
