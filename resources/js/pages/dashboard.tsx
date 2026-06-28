import { Button } from '@/components/ui/button';
import { WorkspaceHero } from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { AdminDashboardComposition } from '@/pages/dashboard-view/admin-dashboard-composition';
import { AthleteDashboardExperience } from '@/pages/dashboard-view/athlete-dashboard-experience';
import { AthleteReferenceComposition } from '@/pages/dashboard-view/athlete-reference-composition';
import { CoachDashboardComposition } from '@/pages/dashboard-view/coach-dashboard-composition';
import { humanizeStatus } from '@/pages/dashboard-view/helpers';
import { dashboardBreadcrumbs, type DashboardPageProps, type Viewer } from '@/pages/dashboard-view/types';
import { Head, Link } from '@inertiajs/react';
import { CreditCard, Dumbbell, Shield, Watch } from 'lucide-react';

const adminPrimaryButtonClass = 'rounded-full bg-stone-950 text-white hover:bg-stone-800';
const adminSecondaryButtonClass = 'rounded-full border-stone-300 bg-white text-stone-900 hover:bg-stone-50';

function viewerSnapshot(viewer: Viewer) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Primary goal</p>
                <p className="mt-3 text-lg font-semibold tracking-tight text-stone-950">{viewer.primaryGoal ?? 'Not set yet'}</p>
            </div>
            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Preferred contact</p>
                <p className="mt-3 text-lg font-semibold tracking-tight text-stone-950">{humanizeStatus(viewer.preferredContactMethod ?? 'email')}</p>
            </div>
            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Contact details</p>
                <p className="mt-3 text-sm font-semibold tracking-tight text-stone-950">{viewer.phone ?? viewer.email}</p>
                <p className="mt-2 text-sm text-stone-600">Signed up through {humanizeStatus(viewer.registrationChannel ?? 'email')}.</p>
            </div>
        </div>
    );
}

function sharedHeroMeta({ viewer, admin, coach }: Pick<DashboardPageProps, 'viewer' | 'admin' | 'coach'>) {
    if (admin && coach) {
        return {
            eyebrow: 'Role-aware command board',
            title: `Admin and coach work both land cleanly now, ${viewer.name}.`,
            description: 'Pick the board you need, act on urgent queues first, and leave the low-value explanation below the fold where it belongs.',
            actions: (
                <>
                    <Button asChild size="lg" className={adminPrimaryButtonClass}>
                        <Link href="/admin/control-center">Open control center</Link>
                    </Button>
                    <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                        <Link href="/roster">Open roster</Link>
                    </Button>
                    <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                        <Link href="/training">Open training</Link>
                    </Button>
                </>
            ),
        };
    }

    if (admin) {
        return {
            eyebrow: 'Admin command board',
            title: `Operations comes first, ${viewer.name}.`,
            description: 'Renewals, payment issues, device blind spots, and live user mix should be obvious the second the dashboard loads.',
            actions: (
                <>
                    <Button asChild size="lg" className={adminPrimaryButtonClass}>
                        <Link href="/admin/control-center">Open control center</Link>
                    </Button>
                    <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                        <Link href="/memberships">Open memberships</Link>
                    </Button>
                    <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                        <Link href="/wearables">Open wearables</Link>
                    </Button>
                </>
            ),
        };
    }

    return {
        eyebrow: 'Coach decision board',
        title: `The roster decisions are up front now, ${viewer.name}.`,
        description:
            'See who needs attention, what is scheduled, what is missing, and which workspace should open next without digging through clutter.',
        actions: (
            <>
                <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                    <Link href="/roster">Open roster</Link>
                </Button>
                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                    <Link href="/training">Open training</Link>
                </Button>
                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                    <Link href="/progress">Open progress</Link>
                </Button>
            </>
        ),
    };
}

function RoleSwitch({ showAdmin, showCoach, showAthleteReference }: { showAdmin: boolean; showCoach: boolean; showAthleteReference: boolean }) {
    const items = [
        showAdmin ? { href: '#admin-board', label: 'Admin board', icon: Shield } : null,
        showCoach ? { href: '#coach-board', label: 'Coach board', icon: Dumbbell } : null,
        showAthleteReference ? { href: '#athlete-reference', label: 'Athlete reference', icon: Watch } : null,
        { href: '/memberships', label: 'Memberships', icon: CreditCard },
    ].filter(Boolean) as Array<{ href: string; label: string; icon: typeof Shield }>;

    return (
        <nav className="flex flex-wrap gap-3">
            {items.map(({ href, label, icon: Icon }) => (
                <a
                    key={label}
                    href={href}
                    className="inline-flex items-center gap-2 rounded-full border border-stone-200/80 bg-white/88 px-4 py-2 text-sm font-medium text-stone-700 transition-colors hover:border-stone-300 hover:text-stone-950"
                >
                    <Icon className="size-4" />
                    {label}
                </a>
            ))}
        </nav>
    );
}

export default function Dashboard({ viewer, admin, coach, athlete }: DashboardPageProps) {
    if (athlete && !admin && !coach) {
        return <AthleteDashboardExperience viewer={viewer} athlete={athlete} />;
    }

    const heroMeta = sharedHeroMeta({ viewer, admin, coach });

    return (
        <AppLayout breadcrumbs={dashboardBreadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow={heroMeta.eyebrow}
                    title={heroMeta.title}
                    description={heroMeta.description}
                    badges={viewer.roles.map((role) => role.label)}
                    actions={heroMeta.actions}
                    aside={viewerSnapshot(viewer)}
                />

                <RoleSwitch showAdmin={Boolean(admin)} showCoach={Boolean(coach)} showAthleteReference={Boolean(athlete)} />

                {admin && <AdminDashboardComposition admin={admin} />}
                {coach && <CoachDashboardComposition coach={coach} />}
                {athlete && <AthleteReferenceComposition athlete={athlete} />}
            </div>
        </AppLayout>
    );
}
