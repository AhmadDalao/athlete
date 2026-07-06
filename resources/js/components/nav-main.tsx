import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    const page = usePage();

    return (
        <div className="space-y-5 px-2">
            {groups.map((group) => (
                <SidebarGroup key={group.title} className="rounded-none border-0 bg-transparent p-0 shadow-none">
                    <SidebarGroupLabel className="px-3 text-[0.68rem] font-semibold tracking-[0.22em] text-lime-200/45 uppercase">
                        {group.title}
                    </SidebarGroupLabel>
                    <SidebarMenu className="mt-1 gap-1">
                        {group.items.map((item) => (
                            <SidebarMenuItem key={`${group.title}-${item.title}`}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={page.url === item.url || page.url.startsWith(`${item.url}?`)}
                                    className="h-11 rounded-2xl border border-transparent px-3 text-stone-300 transition-all hover:border-white/10 hover:bg-white/[0.06] hover:text-white data-[active=true]:border-lime-300/30 data-[active=true]:bg-lime-300 data-[active=true]:text-[#07100c] data-[active=true]:shadow-[0_18px_42px_-30px_rgba(168,255,47,0.75)]"
                                >
                                    <Link href={item.url} prefetch>
                                        {item.icon && <item.icon className="size-4" />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </div>
    );
}
