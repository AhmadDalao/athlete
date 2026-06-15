import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Activity, ArrowRight, CreditCard, Dumbbell, type LucideIcon, Shield, TimerReset, Users, Watch, WifiOff } from 'lucide-react';

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

function MetricCard({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: LucideIcon }) {
    return (
        <Card className="border-stone-200/75 bg-white/92 shadow-[0_24px_60px_-46px_rgba(15,23,42,0.65)]">
            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                    <CardDescription className="text-stone-500">{title}</CardDescription>
                    <CardTitle className="mt-3 text-3xl tracking-tight text-stone-950">{value}</CardTitle>
                </div>
                <div className="rounded-full border border-stone-200 bg-stone-50 p-2 text-stone-700">
                    <Icon className="size-4" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-sm leading-6 text-stone-600">{note}</p>
            </CardContent>
        </Card>
    );
}

function ActionCard({ title, href, note, icon: Icon }: { title: string; href: string; note: string; icon: LucideIcon }) {
    return (
        <Link href={href} className="block">
            <Card className="h-full border-stone-200/75 bg-white/86 transition-transform duration-200 hover:-translate-y-0.5">
                <CardHeader className="flex flex-row items-start justify-between space-y-0">
                    <div>
                        <CardTitle className="text-lg tracking-tight text-stone-950">{title}</CardTitle>
                        <CardDescription className="mt-2 leading-6 text-stone-600">{note}</CardDescription>
                    </div>
                    <div className="rounded-full border border-stone-200 bg-stone-50 p-2 text-stone-700">
                        <Icon className="size-4" />
                    </div>
                </CardHeader>
                <CardContent className="flex items-center text-sm font-medium text-stone-800">
                    Open queue
                    <ArrowRight className="ml-2 size-4" />
                </CardContent>
            </Card>
        </Link>
    );
}

export default function ControlCenter({ summary, queues, signupMix, opsPlaybook }: ControlCenterProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Control Center" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <section className="relative overflow-hidden rounded-[2rem] border border-stone-200/75 bg-[linear-gradient(135deg,rgba(255,251,235,0.96),rgba(248,250,252,0.98)_48%,rgba(240,253,250,0.94))] shadow-[0_30px_80px_-45px_rgba(15,23,42,0.45)]">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.18),transparent_32%),radial-gradient(circle_at_82%_12%,rgba(20,184,166,0.14),transparent_28%),linear-gradient(rgba(148,163,184,0.07)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.07)_1px,transparent_1px)] bg-[length:auto,auto,34px_34px,34px_34px]" />
                    <div className="relative grid gap-6 p-6 lg:grid-cols-[1.12fr_0.88fr] lg:p-8">
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-full border border-stone-300/70 bg-white/85 px-3 py-1 text-[0.68rem] font-semibold tracking-[0.24em] text-stone-600 uppercase">
                                    Admin operations
                                </span>
                                <Badge variant="outline" className="border-stone-300/70 bg-white/75 text-stone-700">
                                    {summary.renewalsThisWeek} renewals this week
                                </Badge>
                                <Badge variant="outline" className="border-stone-300/70 bg-white/75 text-stone-700">
                                    {summary.attentionConnections} device issues
                                </Badge>
                            </div>
                            <div className="space-y-3">
                                <h1 className="max-w-3xl text-4xl leading-none font-semibold tracking-[-0.04em] text-stone-950 sm:text-5xl">
                                    One page for the mess that actually matters.
                                </h1>
                                <p className="max-w-2xl text-sm leading-7 text-stone-600 sm:text-base">
                                    Renewals, failed payments, device blind spots, coach load, and signup readiness sit here so operations does not
                                    dissolve into guesswork and screenshots.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                    <Link href="/admin/users">Manage users</Link>
                                </Button>
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/75">
                                    <Link href="/memberships">Review memberships</Link>
                                </Button>
                            </div>
                        </div>

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
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Active memberships"
                        value={summary.activeMemberships.toString()}
                        note={`${summary.renewalsThisWeek} memberships hit a renewal boundary in the next week.`}
                        icon={CreditCard}
                    />
                    <MetricCard
                        title="Connected devices"
                        value={summary.connectedDevices.toString()}
                        note={`${summary.attentionConnections} links are degraded or dead.`}
                        icon={Watch}
                    />
                    <MetricCard
                        title="Sessions this week"
                        value={summary.scheduledSessionsThisWeek.toString()}
                        note={`${summary.loggedSessionsThisWeek} workout logs were actually submitted this week.`}
                        icon={Activity}
                    />
                    <MetricCard
                        title="Projected monthly revenue"
                        value={formatCurrency(summary.projectedMonthlyRevenue)}
                        note="This is the operating picture, not accounting theater."
                        icon={Shield}
                    />
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <ActionCard title="Users" href="/admin/users" note="Roles, onboarding, and account cleanup." icon={Users} />
                    <ActionCard title="Memberships" href="/memberships" note="Renewals, manual events, and billing states." icon={CreditCard} />
                    <ActionCard title="Wearables" href="/wearables" note="Device health, WHOOP links, and ingest visibility." icon={Watch} />
                    <ActionCard title="Training" href="/training" note="Programs, scheduling, and athlete execution." icon={Dumbbell} />
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Card className="border-stone-200/75 bg-white/92 xl:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Renewal queue</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                The next memberships likely to become support tickets if ignored.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {queues.membershipQueue.length === 0 ? (
                                <p className="text-sm text-stone-500">No renewals need attention right now.</p>
                            ) : (
                                queues.membershipQueue.map((entry) => (
                                    <div
                                        key={`${entry.userName}-${entry.planName}`}
                                        className="flex flex-col gap-3 rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4 md:flex-row md:items-center md:justify-between"
                                    >
                                        <div>
                                            <p className="font-medium text-stone-950">{entry.userName}</p>
                                            <p className="text-sm text-stone-500">
                                                {entry.planName} · {humanizeStatus(entry.userRole)}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                            <Badge variant="outline">{entry.autoRenew ? 'Auto renew on' : 'Manual renewal'}</Badge>
                                            <span className="text-sm text-stone-500">{formatDays(entry.daysRemaining)}</span>
                                            <span className="text-sm text-stone-500">{entry.endsAt ?? 'No end date'}</span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Signup mix</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Current account channels and what is staged for later.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {signupMix.map((entry) => (
                                <div key={entry.method} className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="font-medium text-stone-950">{entry.label}</p>
                                        <Badge variant={entry.enabled ? 'default' : 'secondary'}>{entry.enabled ? 'Live now' : 'Later stage'}</Badge>
                                    </div>
                                    <p className="mt-2 text-sm text-stone-500">{entry.count} account(s)</p>
                                </div>
                            ))}
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
                        <CardContent className="space-y-3">
                            {queues.paymentQueue.length === 0 ? (
                                <p className="text-sm text-stone-500">No payment issues are queued right now.</p>
                            ) : (
                                queues.paymentQueue.map((entry) => (
                                    <div
                                        key={`${entry.userName}-${entry.reference ?? entry.eventAt}`}
                                        className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4"
                                    >
                                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p className="font-medium text-stone-950">{entry.userName}</p>
                                                <p className="text-sm text-stone-500">
                                                    {entry.planName} · {humanizeStatus(entry.userRole)}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                <span className="text-sm text-stone-500">
                                                    {entry.amount === null ? 'No amount' : formatCurrency(entry.amount, entry.currency)}
                                                </span>
                                            </div>
                                        </div>
                                        <p className="mt-3 text-sm leading-6 text-stone-600">
                                            {[entry.provider, entry.reference, entry.eventAt].filter(Boolean).join(' · ') || 'No provider metadata'}
                                        </p>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Device queue</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Wearable relationships most likely to create blind spots.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {queues.deviceQueue.length === 0 ? (
                                <p className="text-sm text-stone-500">No device issues are queued right now.</p>
                            ) : (
                                queues.deviceQueue.map((entry) => (
                                    <div
                                        key={`${entry.userName}-${entry.provider}`}
                                        className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4"
                                    >
                                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p className="font-medium text-stone-950">{entry.userName}</p>
                                                <p className="text-sm text-stone-500">
                                                    {entry.provider} · {humanizeStatus(entry.userRole)}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                <Badge variant="outline">
                                                    {entry.readinessScore === null ? 'No readiness' : `${Math.round(entry.readinessScore)}/100`}
                                                </Badge>
                                            </div>
                                        </div>
                                        <p className="mt-3 text-sm leading-6 text-stone-600">{entry.lastSyncedAt ?? 'Never synced'}</p>
                                    </div>
                                ))
                            )}
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
                        <CardContent className="space-y-3">
                            {queues.athleteCoverageGaps.length === 0 ? (
                                <p className="text-sm text-stone-500">Coverage looks clean right now.</p>
                            ) : (
                                queues.athleteCoverageGaps.map((entry) => (
                                    <div key={entry.email} className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p className="font-medium text-stone-950">{entry.name}</p>
                                                <p className="text-sm text-stone-500">{entry.email}</p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant={badgeVariantForStatus(entry.membershipStatus)}>
                                                    {humanizeStatus(entry.membershipStatus)}
                                                </Badge>
                                                <Badge variant="outline">{entry.membershipPlan}</Badge>
                                            </div>
                                        </div>
                                        <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                            <div className="rounded-xl border border-stone-200/75 bg-white/70 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Coach links</p>
                                                <p className="mt-2 text-sm font-medium text-stone-900">{entry.coachCount}</p>
                                            </div>
                                            <div className="rounded-xl border border-stone-200/75 bg-white/70 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                    Connected devices
                                                </p>
                                                <p className="mt-2 text-sm font-medium text-stone-900">{entry.connectedDevices}</p>
                                            </div>
                                            <div className="rounded-xl border border-stone-200/75 bg-white/70 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Runway</p>
                                                <p className="mt-2 text-sm font-medium text-stone-900">{formatDays(entry.daysRemaining)}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-stone-200/75 bg-white/92">
                        <CardHeader>
                            <CardTitle className="text-xl tracking-tight text-stone-950">Coach load</CardTitle>
                            <CardDescription className="leading-6 text-stone-600">
                                Which coaches are carrying real roster weight and where the cracks are forming.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {queues.coachLoad.length === 0 ? (
                                <p className="text-sm text-stone-500">No coach records are available.</p>
                            ) : (
                                queues.coachLoad.map((entry) => (
                                    <div key={entry.email} className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p className="font-medium text-stone-950">{entry.name}</p>
                                                <p className="text-sm text-stone-500">{entry.email}</p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                <Badge variant="outline">{entry.rosterCount} athletes</Badge>
                                                <Badge variant="outline">{entry.activePrograms} live programs</Badge>
                                            </div>
                                        </div>
                                        <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                            <div className="rounded-xl border border-stone-200/75 bg-white/70 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                    Pending logs
                                                </p>
                                                <p className="mt-2 text-sm font-medium text-stone-900">{entry.pendingLogs}</p>
                                            </div>
                                            <div className="rounded-xl border border-stone-200/75 bg-white/70 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">No device</p>
                                                <p className="mt-2 text-sm font-medium text-stone-900">{entry.athletesWithoutDevice}</p>
                                            </div>
                                            <div className="rounded-xl border border-stone-200/75 bg-white/70 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                    Membership risk
                                                </p>
                                                <p className="mt-2 text-sm font-medium text-stone-900">{entry.membershipsAtRisk}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
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
