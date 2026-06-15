import { type NavItem, type User } from '@/types';
import { CreditCard, Dumbbell, LayoutGrid, LineChart, Shield, Users, Watch } from 'lucide-react';

export function buildMainNavItems(user: User | null): NavItem[] {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        ...(user?.primary_role === 'coach' || user?.primary_role === 'admin'
            ? [
                  {
                      title: 'Roster',
                      url: '/roster',
                      icon: Users,
                  },
              ]
            : []),
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
            title: user?.primary_role === 'athlete' ? 'My membership' : 'Memberships',
            url: '/memberships',
            icon: CreditCard,
        },
        {
            title: 'Wearables',
            url: '/wearables',
            icon: Watch,
        },
    ];

    if (user?.primary_role === 'admin') {
        items.push({
            title: 'Control center',
            url: '/admin/control-center',
            icon: Shield,
        });

        items.push({
            title: 'Users',
            url: '/admin/users',
            icon: Users,
        });
    }

    return items;
}
