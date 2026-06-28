import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Sun } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Appearance settings" description="The product now ships with one consistent light interface." />
                    <div className="rounded-[1.75rem] border border-stone-200/80 bg-white/88 p-6 shadow-[0_22px_48px_-34px_rgba(15,23,42,0.2)]">
                        <div className="flex items-start gap-4">
                            <div className="rounded-2xl bg-[linear-gradient(135deg,rgba(255,247,237,0.95),rgba(236,253,245,0.95))] p-3 text-stone-900">
                                <Sun className="size-5" />
                            </div>
                            <div className="space-y-3">
                                <p className="font-medium text-stone-950">Dark mode is gone.</p>
                                <p className="text-sm leading-7 text-stone-600">
                                    The interface is locked to a light system so dashboards, forms, and charts stay visually consistent across the
                                    product. The previous mixed theme setup made the UX worse, so it got cut.
                                </p>
                                <AppearanceTabs />
                            </div>
                        </div>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
