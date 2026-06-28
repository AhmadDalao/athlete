import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    const page = usePage();

    return (
        <div className="space-y-5 px-2">
            {groups.map((group) => (
                <SidebarGroup key={group.title} className="rounded-none border-0 bg-transparent p-0 shadow-none">
                    <SidebarGroupLabel className="px-3 text-[0.68rem] font-semibold tracking-[0.22em] text-stone-400 uppercase">
                        {group.title}
                    </SidebarGroupLabel>
                    <SidebarMenu className="mt-1 gap-1">
                        {group.items.map((item) => (
                            <SidebarMenuItem key={`${group.title}-${item.title}`}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={page.url === item.url || page.url.startsWith(`${item.url}?`)}
                                    className="h-11 rounded-2xl border border-transparent px-3 text-stone-600 transition-all hover:bg-stone-100 hover:text-stone-950 data-[active=true]:border-stone-200/90 data-[active=true]:bg-[#efe2cf] data-[active=true]:text-stone-950 data-[active=true]:shadow-none"
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
