import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    WorkspaceActionCard,
    WorkspaceHero,
    WorkspaceMetricCard,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
} from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Activity, CreditCard, Dumbbell, Shield, TimerReset, Users, Watch, WifiOff } from 'lucide-react';

const adminPrimaryButtonClass =
    'rounded-full border border-emerald-300/70 bg-[linear-gradient(135deg,rgba(16,185,129,0.96),rgba(13,148,136,0.92))] text-white shadow-[0_18px_34px_-24px_rgba(5,150,105,0.45)] hover:brightness-[1.03]';
const adminSecondaryButtonClass =
    'rounded-full border-emerald-200/80 bg-[linear-gradient(135deg,rgba(236,253,245,0.98),rgba(255,251,235,0.95))] text-emerald-900 hover:border-emerald-300 hover:bg-[linear-gradient(135deg,rgba(220,252,231,1),rgba(254,249,195,0.92))]';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Control center',
        href: '/admin/control-center',
    },
];

interface MetricSummary {
    totalUsers: number;
    admins: number;
    coaches: number;
    athletes: number;
    activeMemberships: number;
    renewalsThisWeek: number;
    projectedMonthlyRevenue: number;
    paymentVolumeThisMonth: number;
    failedPaymentsThisMonth: number;
    connectedDevices: number;
    attentionConnections: number;
    whoopConnections: number;
    activePrograms: number;
    scheduledSessionsThisWeek: number;
    loggedSessionsThisWeek: number;
    athletesWithCoverageGaps: number;
}

interface MembershipQueueItem {
    userName: string;
    userRole: string | null;
    planName: string;
    status: string;
    daysRemaining: number | null;
    autoRenew: boolean;
    endsAt: string | null;
}

interface PaymentQueueItem {
    userName: string;
    userRole: string | null;
    planName: string;
    status: string;
    amount: number | null;
    currency: string;
    provider: string | null;
    reference: string | null;
    eventAt: string | null;
}

interface DeviceQueueItem {
    userName: string;
    userRole: string | null;
    provider: string;
    status: string;
    lastSyncedAt: string | null;
    readinessScore: number | null;
    severity: string;
    issue: string;
    recommendation: string;
    lastErrorMessage: string | null;
    lastErrorAt: string | null;
    staleHours: number | null;
    staleDays: number | null;
    syncFailuresCount: number;
}

interface AthleteCoverageGap {
    name: string;
    email: string;
    membershipStatus: string;
    membershipPlan: string;
    coachCount: number;
    connectedDevices: number;
    daysRemaining: number | null;
}

interface CoachLoadItem {
    name: string;
    email: string;
    rosterCount: number;
    activePrograms: number;
    pendingLogs: number;
    athletesWithoutDevice: number;
    membershipsAtRisk: number;
}

interface SignupMixItem {
    method: string;
    label: string;
    enabled: boolean;
    count: number;
}

interface OpsPlaybookItem {
    title: string;
    command: string;
    cadence: string;
    reason: string;
}

interface ControlCenterProps {
    summary: MetricSummary;
    queues: {
        membershipQueue: MembershipQueueItem[];
        paymentQueue: PaymentQueueItem[];
        deviceQueue: DeviceQueueItem[];
        athleteCoverageGaps: AthleteCoverageGap[];
        coachLoad: CoachLoadItem[];
    };
    signupMix: SignupMixItem[];
    opsPlaybook: OpsPlaybookItem[];
}

function formatCurrency(value: number, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDays(days: number | null) {
    if (days === null) {
        return 'No end date';
    }

    if (days === 0) {
        return 'Ends today';
    }

    if (days === 1) {
        return '1 day left';
    }

    return `${days} days left`;
}

function humanizeStatus(status: string | null) {
    if (!status) {
        return 'Unknown';
    }

    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function badgeVariantForStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['past_due', 'cancelled', 'expired', 'failed', 'disconnected'].includes(status)) {
        return 'destructive';
    }

    if (['grace', 'pending', 'attention', 'none'].includes(status)) {
        return 'secondary';
    }

    if (['trialing', 'active', 'connected', 'succeeded'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

export default function ControlCenter({ summary, queues, signupMix, opsPlaybook }: ControlCenterProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Control Center" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <WorkspaceHero
                    eyebrow="Admin operations"
                    title="One page for the mess that actually matters."
                    description="Renewals, failed payments, device blind spots, coach load, and signup readiness sit here so operations does not dissolve into guesswork and screenshots."
                    badges={[`${summary.renewalsThisWeek} renewals this week`, `${summary.attentionConnections} device issues`]}
                    actions={
                        <>
                            <Button asChild size="lg" className={adminPrimaryButtonClass}>
                                <Link href="/admin/users">Manage users</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                                <Link href="/memberships">Review memberships</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-[1.5rem] border border-white/75 bg-white/82 p-5">
                                <p className="text-[0.68rem] font-semibold tracking-[0.24em] text-stone-500 uppercase">Revenue track</p>
                                <p className="mt-3 text-4xl font-semibold tracking-tight text-stone-950">
                                    {formatCurrency(summary.projectedMonthlyRevenue)}
                                </p>
                                <p className="mt-3 text-sm leading-6 text-stone-600">
                                    {formatCurrency(summary.paymentVolumeThisMonth)} collected this month and {summary.failedPaymentsThisMonth}{' '}
                                    failures still staring at you.
                                </p>
                            </div>
                            <div className="rounded-[1.5rem] border border-white/75 bg-white/82 p-5">
                                <p className="text-[0.68rem] font-semibold tracking-[0.24em] text-stone-500 uppercase">Coverage gaps</p>
                                <p className="mt-3 text-4xl font-semibold tracking-tight text-stone-950">{summary.athletesWithCoverageGaps}</p>
                                <p className="mt-3 text-sm leading-6 text-stone-600">
                                    Athletes with missing coach coverage, missing device signal, or a membership problem.
                                </p>
                            </div>
                            <div className="rounded-[1.5rem] border border-white/75 bg-white/82 p-5 sm:col-span-2">
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-[0.68rem] font-semibold tracking-[0.24em] text-stone-500 uppercase">Live platform mix</p>
                                        <p className="mt-2 text-sm leading-6 text-stone-600">
                                            {summary.totalUsers} users: {summary.admins} admins, {summary.coaches} coaches, {summary.athletes}{' '}
                                            athletes.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="outline" className="border-stone-300/70 bg-white/75 text-stone-700">
                                            {summary.connectedDevices} connected devices
                                        </Badge>
                                        <Badge variant="outline" className="border-stone-300/70 bg-white/75 text-stone-700">
                                            {summary.whoopConnections} WHOOP links
                                        </Badge>
                                        <Badge variant="outline" className="border-stone-300/70 bg-white/75 text-stone-700">
                                            {summary.activePrograms} active programs
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Active memberships"
                        value={summary.activeMemberships.toString()}
                        note={`${summary.renewalsThisWeek} memberships hit a renewal boundary in the next week.`}
                        icon={CreditCard}
                    />
                    <WorkspaceMetricCard
                        title="Connected devices"
                        value={summary.connectedDevices.toString()}
                        note={`${summary.attentionConnections} links are degraded or dead.`}
                        icon={Watch}
                    />
                    <WorkspaceMetricCard
                        title="Sessions this week"
                        value={summary.scheduledSessionsThisWeek.toString()}
                        note={`${summary.loggedSessionsThisWeek} workout logs were actually submitted this week.`}
                        icon={Activity}
                    />
                    <WorkspaceMetricCard
                        title="Projected monthly revenue"
                        value={formatCurrency(summary.projectedMonthlyRevenue)}
                        note="This is the operating picture, not accounting theater."
                        icon={Shield}
                    />
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceActionCard title="Users" href="/admin/users" note="Roles, onboarding, and account cleanup." icon={Users} />
                    <WorkspaceActionCard
                        title="Memberships"
                        href="/memberships"
                        note="Renewals, manual events, and billing states."
                        icon={CreditCard}
                    />
                    <WorkspaceActionCard title="Wearables" href="/wearables" note="Device health, WHOOP links, and ingest visibility." icon={Watch} />
                    <WorkspaceActionCard title="Training" href="/training" note="Programs, scheduling, and athlete execution." icon={Dumbbell} />
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Card className="border-stone-200/75 bg-white/92 xl:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Renewal queue</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                The next memberships likely to become support tickets if ignored.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceTable minWidth="min-w-[760px]">
                                <WorkspaceTableHeader labels={['User', 'Role', 'Plan', 'Status', 'Renewal', 'Ends']} />
                                {queues.membershipQueue.length === 0 ? (
                                    <WorkspaceTableEmpty message="No renewals need attention right now." colSpan={6} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {queues.membershipQueue.map((entry) => (
                                            <tr key={`${entry.userName}-${entry.planName}`} className="align-top hover:bg-stone-50/80">
                                                <td className="px-4 py-4 font-semibold text-stone-950">{entry.userName}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{humanizeStatus(entry.userRole)}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.planName}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <Badge variant="outline">{entry.autoRenew ? 'Auto renew on' : 'Manual renewal'}</Badge>
                                                    <p className="mt-1 text-xs text-stone-500">{formatDays(entry.daysRemaining)}</p>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.endsAt ?? 'No end date'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </CardContent>
                    </Card>

                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Signup mix</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Current account channels and what is staged for later.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceTable minWidth="min-w-[520px]">
                                <WorkspaceTableHeader labels={['Channel', 'Status', 'Accounts']} />
                                <tbody className="divide-y divide-stone-100">
                                    {signupMix.map((entry) => (
                                        <tr key={entry.method} className="align-top hover:bg-stone-50/80">
                                            <td className="px-4 py-4 font-semibold text-stone-950">{entry.label}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant={entry.enabled ? 'default' : 'secondary'}>
                                                    {entry.enabled ? 'Live now' : 'Later stage'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-stone-700">{entry.count}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </WorkspaceTable>
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Payment queue</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Pending and failed money events still waiting for a human to care.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceTable minWidth="min-w-[760px]">
                                <WorkspaceTableHeader labels={['User', 'Role', 'Plan', 'Status', 'Amount', 'Provider', 'Reference', 'Date']} />
                                {queues.paymentQueue.length === 0 ? (
                                    <WorkspaceTableEmpty message="No payment issues are queued right now." colSpan={8} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {queues.paymentQueue.map((entry) => (
                                            <tr key={`${entry.userName}-${entry.reference ?? entry.eventAt}`} className="align-top hover:bg-stone-50/80">
                                                <td className="px-4 py-4 font-semibold text-stone-950">{entry.userName}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{humanizeStatus(entry.userRole)}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.planName}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                </td>
                                                <td className="px-4 py-4 font-medium text-stone-950">
                                                    {entry.amount === null ? 'No amount' : formatCurrency(entry.amount, entry.currency)}
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.provider ?? 'Manual'}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.reference ?? 'No reference'}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.eventAt ?? 'No date'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </CardContent>
                    </Card>

                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Device queue</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Wearable relationships most likely to create blind spots.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceTable minWidth="min-w-[920px]">
                                <WorkspaceTableHeader
                                    labels={['User', 'Provider', 'Status', 'Readiness', 'Last sync', 'Failures', 'Issue', 'Recommendation']}
                                />
                                {queues.deviceQueue.length === 0 ? (
                                    <WorkspaceTableEmpty message="No device issues are queued right now." colSpan={8} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {queues.deviceQueue.map((entry) => (
                                            <tr key={`${entry.userName}-${entry.provider}`} className="align-top hover:bg-stone-50/80">
                                                <td className="px-4 py-4">
                                                    <p className="font-semibold text-stone-950">{entry.userName}</p>
                                                    <p className="mt-1 text-xs text-stone-500">{humanizeStatus(entry.userRole)}</p>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.provider}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">
                                                    {entry.readinessScore === null ? 'No readiness' : `${Math.round(entry.readinessScore)}/100`}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <p className="text-sm text-stone-700">{entry.lastSyncedAt ?? 'Never synced'}</p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        {entry.staleHours !== null ? `${entry.staleHours}h stale` : 'No stale data'}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.syncFailuresCount}</td>
                                                <td className="px-4 py-4">
                                                    <p className="max-w-[16rem] text-sm leading-6 text-stone-700">{entry.issue}</p>
                                                    {entry.lastErrorMessage && (
                                                        <p className="mt-1 line-clamp-2 max-w-[16rem] text-xs text-stone-500">
                                                            {entry.lastErrorMessage}
                                                        </p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <p className="max-w-[16rem] text-sm leading-6 text-stone-700">{entry.recommendation}</p>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Athlete coverage gaps</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Athletes missing a coach, a clean membership state, or a usable device signal.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceTable minWidth="min-w-[780px]">
                                <WorkspaceTableHeader
                                    labels={['Athlete', 'Email', 'Membership', 'Plan', 'Runway', 'Coach links', 'Devices']}
                                />
                                {queues.athleteCoverageGaps.length === 0 ? (
                                    <WorkspaceTableEmpty message="Coverage looks clean right now." colSpan={7} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {queues.athleteCoverageGaps.map((entry) => (
                                            <tr key={entry.email} className="align-top hover:bg-stone-50/80">
                                                <td className="px-4 py-4 font-semibold text-stone-950">{entry.name}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.email}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={badgeVariantForStatus(entry.membershipStatus)}>
                                                        {humanizeStatus(entry.membershipStatus)}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.membershipPlan}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{formatDays(entry.daysRemaining)}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.coachCount}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.connectedDevices}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </CardContent>
                    </Card>

                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Coach load</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Which coaches are carrying real roster weight and where the cracks are forming.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkspaceTable minWidth="min-w-[780px]">
                                <WorkspaceTableHeader
                                    labels={['Coach', 'Email', 'Roster', 'Live programs', 'Pending logs', 'No device', 'Membership risk']}
                                />
                                {queues.coachLoad.length === 0 ? (
                                    <WorkspaceTableEmpty message="No coach records are available." colSpan={7} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {queues.coachLoad.map((entry) => (
                                            <tr key={entry.email} className="align-top hover:bg-stone-50/80">
                                                <td className="px-4 py-4 font-semibold text-stone-950">{entry.name}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.email}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.rosterCount}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.activePrograms}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.pendingLogs}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.athletesWithoutDevice}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{entry.membershipsAtRisk}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </CardContent>
                    </Card>
                </section>

                <Card className="border-stone-200/75 bg-white/92">
                    <CardHeader>
                        <CardTitle className="text-xl tracking-tight text-stone-950">Ops playbook</CardTitle>
                        <CardDescription className="leading-6 text-stone-600">
                            Commands and automations the dev team should keep alive once this leaves the sandbox.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 lg:grid-cols-3">
                        {opsPlaybook.map((entry) => (
                            <div key={entry.title} className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="font-medium text-stone-950">{entry.title}</p>
                                        <p className="mt-1 text-sm text-stone-500">{entry.cadence}</p>
                                    </div>
                                    <div className="rounded-full border border-stone-200 bg-white/70 p-2 text-stone-700">
                                        {entry.title.includes('WHOOP') ? (
                                            <Watch className="size-4" />
                                        ) : entry.title.includes('Membership') ? (
                                            <TimerReset className="size-4" />
                                        ) : (
                                            <WifiOff className="size-4" />
                                        )}
                                    </div>
                                </div>
                                <p className="mt-4 rounded-xl border border-stone-200/75 bg-white/80 px-3 py-2 font-mono text-xs text-stone-800">
                                    {entry.command}
                                </p>
                                <p className="mt-4 text-sm leading-6 text-stone-600">{entry.reason}</p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
