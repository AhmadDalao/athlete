import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { buildMainNavGroups } from '@/lib/navigation';
import { type AuthenticatedSharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Cable, LifeBuoy } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<AuthenticatedSharedData>().props;
    const navGroups = buildMainNavGroups(auth.user);
    const isAdminLike = auth.user.primary_role === 'owner' || auth.user.primary_role === 'admin';

    return (
        <Sidebar collapsible="icon" variant="sidebar" className="border-r border-stone-200 bg-[#fbf7ef] text-stone-950">
            <SidebarHeader className="gap-5 px-4 py-5">
                <SidebarMenu className="border-b border-stone-200 pb-5">
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="h-auto rounded-2xl px-2 py-2 hover:bg-[#f4ead9]">
                            <Link href={auth.user.landing_path ?? '/app'} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <div className="rounded-2xl border border-[#e8d7b9] bg-[#fffdf8] p-4 shadow-[0_18px_44px_-38px_rgba(22,18,11,0.3)] group-data-[collapsible=icon]:hidden">
                    <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-[#9a6a1f] uppercase">Workspace</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">
                        Run operations, rosters, training, memberships, and integrations without hunting through clutter.
                    </p>
                </div>
            </SidebarHeader>

            <SidebarContent className="pb-2">
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter className="gap-3 border-t border-stone-200 px-4 py-4">
                <div className="grid gap-2 group-data-[collapsible=icon]:hidden">
                    <Button asChild size="sm" variant="outline" className="justify-start rounded-xl border-stone-200 bg-white text-stone-700 hover:bg-[#fffaf0] hover:text-stone-950">
                        <Link href="/contact">
                            <LifeBuoy className="size-4" />
                            Contact us
                        </Link>
                    </Button>
                    {isAdminLike && (
                        <Button asChild size="sm" variant="ghost" className="justify-start rounded-xl text-stone-600 hover:bg-[#fffaf0] hover:text-stone-950">
                            <Link href="/api-access">
                                <Cable className="size-4" />
                                API docs and keys
                            </Link>
                        </Button>
                    )}
                </div>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
