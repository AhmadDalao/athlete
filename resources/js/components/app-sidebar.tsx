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
        <Sidebar collapsible="icon" variant="sidebar" className="border-r border-white/10 bg-[#090f11] text-stone-50">
            <SidebarHeader className="gap-5 px-4 py-5">
                <SidebarMenu className="border-b border-white/10 pb-5">
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="h-auto rounded-2xl px-2 py-2 hover:bg-white/5">
                            <Link href={auth.user.landing_path ?? '/app'} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <div className="rounded-2xl border border-lime-300/15 bg-white/[0.04] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] group-data-[collapsible=icon]:hidden">
                    <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-lime-200/75 uppercase">Workspace</p>
                    <p className="mt-2 text-sm leading-6 text-stone-300">
                        Run operations, rosters, training, memberships, and integrations without hunting through clutter.
                    </p>
                </div>
            </SidebarHeader>

            <SidebarContent className="pb-2">
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter className="gap-3 border-t border-white/10 px-4 py-4">
                <div className="grid gap-2 group-data-[collapsible=icon]:hidden">
                    <Button asChild size="sm" variant="outline" className="justify-start rounded-xl border-white/10 bg-white/[0.05] text-stone-100 hover:bg-white/10 hover:text-white">
                        <Link href="/contact">
                            <LifeBuoy className="size-4" />
                            Contact us
                        </Link>
                    </Button>
                    {isAdminLike && (
                        <Button asChild size="sm" variant="ghost" className="justify-start rounded-xl text-stone-300 hover:bg-white/10 hover:text-white">
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
