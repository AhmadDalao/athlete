import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading, ReadinessDial, TrendBars } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    WorkspaceHero,
    WorkspaceMetricCard,
    WorkspacePanel,
    WorkspaceSectionHeading,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
} from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, AlertTriangle, HeartPulse, MoonStar, ShieldCheck, Watch } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wearables',
        href: '/wearables',
    },
];

const statusFilters = [
    { label: 'All', value: null },
    { label: 'Connected', value: 'connected' },
    { label: 'Attention', value: 'attention' },
    { label: 'Disconnected', value: 'disconnected' },
] as const;

interface LatestSnapshot {
    metricDate: string | null;
    readinessScore: number | null;
    readinessBand: string | null;
    strainScore: number | null;
    sleepHours: number | null;
    sleepNeedHours: number | null;
    sleepDebtHours: number | null;
    sleepPerformancePercentage: number | null;
    sleepConsistencyPercentage: number | null;
    steps: number | null;
    trainingLoad: number | null;
    restingHeartRate: number | null;
    heartRateVariability: number | null;
    respiratoryRate: number | null;
    bloodOxygenPercent: number | null;
    skinTemperatureCelsius: number | null;
}

interface MetricsAnalytics {
    latest: {
        metricDate: string | null;
        readinessScore: number | null;
        readinessBand: string | null;
        strainScore: number | null;
        sleepHours: number | null;
        sleepNeedHours: number | null;
        sleepDebtHours: number | null;
        restingHeartRate: number | null;
        heartRateVariability: number | null;
        trainingLoad: number | null;
    } | null;
    overview: {
        daysTracked: number;
        averageReadiness: number | null;
        averageSleepHours: number | null;
        averageStrain: number | null;
        totalTrainingLoad: number | null;
        averageRestingHeartRate: number | null;
        averageHrv: number | null;
        averageSleepPerformance: number | null;
        averageSleepConsistency: number | null;
        averageRespiratoryRate: number | null;
        averageBloodOxygen: number | null;
        averageSleepDebtHours: number | null;
        readinessBand: string | null;
        readinessDelta: number | null;
        lowReadinessDays: number;
    };
    timeline: Array<{
        metricDate: string | null;
        readinessScore: number | null;
        sleepHours: number | null;
        strainScore: number | null;
        trainingLoad: number | null;
    }>;
    alerts: string[];
}

interface WearableConnection {
    id: number;
    publicId: string;
    provider: string;
    providerLabel: string;
    status: string;
    authType: string;
    userName: string;
    userEmail: string;
    userRole: string | null;
    grantedScopes: string[];
    lastSyncedAt: string | null;
    ingest: {
        key: string;
        lastFour: string;
        path: string;
    } | null;
    latestSnapshot: LatestSnapshot | null;
    analytics: MetricsAnalytics;
    review: {
        severity: string;
        issue: string;
        recommendation: string;
        lastErrorMessage: string | null;
        lastErrorAt: string | null;
        staleHours: number | null;
        staleDays: number | null;
        syncFailuresCount: number;
    };
}

interface WearablePaginator {
    data: WearableConnection[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface WearablesPageProps {
    viewerRole: string | null;
    scopeLabel: string;
    filters: {
        status: string | null;
    };
    summary: {
        totalConnections: number;
        healthyConnections: number;
        attentionRequired: number;
        syncedToday: number;
        averageReadiness: number | null;
    };
    connections: WearablePaginator;
    reviewQueue: Array<{
        id: number;
        userName: string;
        providerLabel: string;
        status: string;
        severity: string;
        issue: string;
        recommendation: string;
        lastErrorMessage: string | null;
        lastErrorAt: string | null;
        staleHours: number | null;
        staleDays: number | null;
        syncFailuresCount: number;
    }>;
    whoopIntegration: {
        oauthReady: boolean;
        connectUrl: string;
        scopes: string[];
        lookbackDays: number;
        capabilities: string[];
        connectedCount: number;
        failingCount: number;
        webhookReady: boolean;
        webhookUrl: string;
        pendingEvents: number;
        failedEvents: number;
        lastReceivedAt: string | null;
        syncCommand: string;
        webhookProcessCommand: string;
    };
    sampleCurl: string;
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function badgeVariantForStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'disconnected') {
        return 'destructive';
    }

    if (status === 'attention') {
        return 'secondary';
    }

    if (status === 'connected') {
        return 'default';
    }

    return 'outline';
}

function badgeVariantForSeverity(severity: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (severity === 'high') {
        return 'destructive';
    }

    if (severity === 'medium') {
        return 'secondary';
    }

    if (severity === 'stable') {
        return 'default';
    }

    return 'outline';
}

function formatReadiness(value: number | null) {
    if (value === null) {
        return 'No readiness data';
    }

    return `${Math.round(value)}/100`;
}

function formatSleepHours(value: number | null) {
    if (value === null) {
        return 'No sleep data';
    }

    return `${value.toFixed(1)}h`;
}

function formatPercentage(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${Math.round(value)}%`;
}

function formatSleepDebt(value: number | null) {
    if (value === null) {
        return 'No sleep-need data';
    }

    if (value <= 0) {
        return `${Math.abs(value).toFixed(1)}h banked`;
    }

    return `${value.toFixed(1)}h behind`;
}

function shortDayLabel(value: string | null) {
    if (!value) {
        return 'No date';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function AthleteWearablesExperience({
    summary,
    connections,
    whoopIntegration,
}: Pick<WearablesPageProps, 'summary' | 'connections' | 'whoopIntegration'>) {
    const primaryConnection = connections.data[0] ?? null;
    const latestSnapshot = primaryConnection?.latestSnapshot ?? null;
    const readinessTimeline =
        primaryConnection?.analytics.timeline.slice(-7).map((entry) => ({
            label: shortDayLabel(entry.metricDate),
            value: entry.readinessScore,
            note: entry.trainingLoad === null ? undefined : `load ${Math.round(entry.trainingLoad)}`,
        })) ?? [];
    const sleepTimeline =
        primaryConnection?.analytics.timeline.slice(-7).map((entry) => ({
            label: shortDayLabel(entry.metricDate),
            value: entry.sleepHours,
            note: entry.strainScore === null ? undefined : `strain ${Math.round(entry.strainScore)}`,
        })) ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Wearables" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <AthleteHero
                    eyebrow="Recovery signal board"
                    title={
                        primaryConnection
                            ? `${primaryConnection.providerLabel} is feeding the board.`
                            : 'Connect a device and start tracking recovery for real.'
                    }
                    description={
                        primaryConnection
                            ? 'Readiness, sleep quality, strain, and daily physiology now live where the athlete and coach can actually use them.'
                            : 'Without a device connection the recovery side of the platform is blind. MVP or not, that part is non-negotiable.'
                    }
                    badges={
                        primaryConnection
                            ? [
                                  humanizeStatus(primaryConnection.status),
                                  `${primaryConnection.analytics.overview.daysTracked} tracked days`,
                                  humanizeStatus(primaryConnection.authType),
                              ]
                            : ['No live connection']
                    }
                    actions={
                        <>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/dashboard">Back to dashboard</Link>
                            </Button>
                            {whoopIntegration.oauthReady && (
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                    <Link href={whoopIntegration.connectUrl}>Connect WHOOP</Link>
                                </Button>
                            )}
                        </>
                    }
                >
                    <div className="space-y-4">
                        <ReadinessDial
                            score={latestSnapshot?.readinessScore ?? summary.averageReadiness}
                            label={latestSnapshot?.metricDate ? `Latest sync · ${shortDayLabel(latestSnapshot.metricDate)}` : 'Latest sync'}
                            note={formatReadiness(latestSnapshot?.readinessScore ?? summary.averageReadiness)}
                            detail={
                                latestSnapshot
                                    ? `Sleep ${formatSleepHours(latestSnapshot.sleepHours)} · strain ${latestSnapshot.strainScore ?? 'N/A'} · debt ${formatSleepDebt(latestSnapshot.sleepDebtHours)}`
                                    : 'No normalized snapshot has landed yet.'
                            }
                        />
                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Sleep need</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {latestSnapshot?.sleepNeedHours === null || latestSnapshot?.sleepNeedHours === undefined
                                        ? 'N/A'
                                        : `${latestSnapshot.sleepNeedHours.toFixed(1)}h`}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">How much sleep the current cycle says you actually needed.</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Training load</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {latestSnapshot?.trainingLoad === null || latestSnapshot?.trainingLoad === undefined
                                        ? 'N/A'
                                        : Math.round(latestSnapshot.trainingLoad)}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">A quick signal on what your body thinks yesterday cost.</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Connection status</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {primaryConnection ? humanizeStatus(primaryConnection.status) : 'Disconnected'}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">{primaryConnection?.lastSyncedAt ?? 'No sync timestamp yet.'}</p>
                            </div>
                        </div>
                    </div>
                </AthleteHero>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <AthleteMetricCard
                        title="Tracked connections"
                        value={summary.totalConnections.toString()}
                        note={`${summary.syncedToday} synced today.`}
                        icon={Watch}
                    />
                    <AthleteMetricCard
                        title="Healthy connections"
                        value={summary.healthyConnections.toString()}
                        note="Connections currently delivering signal."
                        icon={ShieldCheck}
                    />
                    <AthleteMetricCard
                        title="Average readiness"
                        value={formatReadiness(summary.averageReadiness)}
                        note="Across the currently visible connection set."
                        icon={HeartPulse}
                        tone="amber"
                    />
                    <AthleteMetricCard
                        title="Sleep debt"
                        value={formatSleepDebt(latestSnapshot?.sleepDebtHours ?? null)}
                        note="Banked sleep beats fake toughness."
                        icon={MoonStar}
                        tone="stone"
                    />
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Trend view"
                        title="Watch the last week, not just the last number."
                        description="Single-day metrics lie when taken alone. Trend shape matters more than cherry-picked hero stats."
                    />
                    <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                        <AthletePanel
                            title="Recovery trend"
                            description="Readiness and sleep plotted against the last visible week."
                            contentClassName="space-y-4"
                        >
                            {primaryConnection ? (
                                <div className="grid gap-4 lg:grid-cols-2">
                                    <TrendBars
                                        title="Readiness"
                                        description="Preparedness across the recent window."
                                        points={readinessTimeline}
                                        formatter={(value) => (value === null ? 'N/A' : `${Math.round(value)}`)}
                                        tone="teal"
                                    />
                                    <TrendBars
                                        title="Sleep hours"
                                        description="Actual sleep the body got."
                                        points={sleepTimeline}
                                        formatter={(value) => (value === null ? 'N/A' : `${value.toFixed(1)}h`)}
                                        tone="amber"
                                    />
                                </div>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-5 text-sm leading-6 text-stone-500">
                                    No connection data yet, so no trend. Pretty obvious.
                                </div>
                            )}
                            {primaryConnection?.analytics.alerts.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {primaryConnection.analytics.alerts.map((alert) => (
                                        <Badge key={alert} variant="secondary">
                                            {alert}
                                        </Badge>
                                    ))}
                                </div>
                            ) : null}
                        </AthletePanel>

                        <AthletePanel
                            title="Latest physiology"
                            description="The freshest normalized metrics pulled into the athlete board."
                            contentClassName="grid gap-3 sm:grid-cols-2"
                        >
                            <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Sleep performance</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">
                                    {formatPercentage(latestSnapshot?.sleepPerformancePercentage ?? null)}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Sleep consistency</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">
                                    {formatPercentage(latestSnapshot?.sleepConsistencyPercentage ?? null)}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">HRV</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">
                                    {latestSnapshot?.heartRateVariability === null || latestSnapshot?.heartRateVariability === undefined
                                        ? 'N/A'
                                        : `${Math.round(latestSnapshot.heartRateVariability)} ms`}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Resting HR</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">
                                    {latestSnapshot?.restingHeartRate === null || latestSnapshot?.restingHeartRate === undefined
                                        ? 'N/A'
                                        : `${latestSnapshot.restingHeartRate} bpm`}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Respiratory</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">
                                    {latestSnapshot?.respiratoryRate === null || latestSnapshot?.respiratoryRate === undefined
                                        ? 'N/A'
                                        : `${latestSnapshot.respiratoryRate.toFixed(1)} rpm`}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Blood oxygen</p>
                                <p className="mt-2 text-lg font-semibold text-stone-950">
                                    {latestSnapshot?.bloodOxygenPercent === null || latestSnapshot?.bloodOxygenPercent === undefined
                                        ? 'N/A'
                                        : `${latestSnapshot.bloodOxygenPercent}%`}
                                </p>
                            </div>
                        </AthletePanel>
                    </div>
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Connections"
                        title="Every linked device and what it last reported."
                        description="This is the athlete-facing version of device truth: status, sync timing, and the last meaningful recovery snapshot."
                    />
                    <div className="grid gap-4 xl:grid-cols-2">
                        {connections.data.length === 0 ? (
                            <AthletePanel
                                title="No device connections yet"
                                description="OAuth or ingest can both work. What does not work is zero data."
                                contentClassName="p-0"
                            >
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-6 text-sm leading-6 text-stone-500">
                                    {whoopIntegration.oauthReady
                                        ? 'WHOOP OAuth is ready if you want to connect it now.'
                                        : 'Fallback ingest still works until OAuth credentials are configured.'}
                                </div>
                            </AthletePanel>
                        ) : (
                            connections.data.map((connection) => (
                                <AthletePanel
                                    key={connection.id}
                                    title={connection.providerLabel}
                                    description={`${humanizeStatus(connection.status)} · ${connection.lastSyncedAt ?? 'Never synced'}`}
                                    contentClassName="space-y-4"
                                >
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant={badgeVariantForStatus(connection.status)}>{humanizeStatus(connection.status)}</Badge>
                                        <Badge variant="outline">{humanizeStatus(connection.authType)}</Badge>
                                        {connection.grantedScopes.map((scope) => (
                                            <Badge key={scope} variant="outline">
                                                {scope}
                                            </Badge>
                                        ))}
                                    </div>
                                    {connection.latestSnapshot ? (
                                        <>
                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div className="rounded-xl border border-stone-200/75 bg-stone-50/80 p-3">
                                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                        Readiness
                                                    </p>
                                                    <p className="mt-2 text-sm font-medium text-stone-950">
                                                        {formatReadiness(connection.latestSnapshot.readinessScore)}
                                                    </p>
                                                </div>
                                                <div className="rounded-xl border border-stone-200/75 bg-stone-50/80 p-3">
                                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                        Sleep debt
                                                    </p>
                                                    <p className="mt-2 text-sm font-medium text-stone-950">
                                                        {formatSleepDebt(connection.latestSnapshot.sleepDebtHours)}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="grid gap-2">
                                                {connection.analytics.timeline.map((entry) => (
                                                    <div
                                                        key={entry.metricDate ?? 'unknown'}
                                                        className="grid gap-2 rounded-xl border border-stone-200/75 bg-stone-50/80 p-3 text-sm text-stone-600 md:grid-cols-4"
                                                    >
                                                        <p className="font-medium text-stone-950">{shortDayLabel(entry.metricDate)}</p>
                                                        <p>Readiness {formatReadiness(entry.readinessScore)}</p>
                                                        <p>Sleep {formatSleepHours(entry.sleepHours)}</p>
                                                        <p>Load {entry.trainingLoad ?? 'N/A'}</p>
                                                    </div>
                                                ))}
                                            </div>
                                        </>
                                    ) : (
                                        <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-5 text-sm leading-6 text-stone-500">
                                            No normalized snapshot has landed for this connection yet.
                                        </div>
                                    )}
                                    {connection.ingest && (
                                        <div className="min-w-0 rounded-xl border border-stone-200/75 bg-stone-50/80 p-4">
                                            <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                Fallback ingest endpoint
                                            </p>
                                            <code className="mt-2 block text-xs leading-6 break-all text-stone-700">{connection.ingest.path}</code>
                                        </div>
                                    )}
                                </AthletePanel>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

export default function WearablesIndex({
    viewerRole,
    scopeLabel,
    filters,
    summary,
    connections,
    reviewQueue,
    whoopIntegration,
    sampleCurl,
}: WearablesPageProps) {
    const page = usePage<SharedData>();

    if (viewerRole === 'athlete') {
        return <AthleteWearablesExperience summary={summary} connections={connections} whoopIntegration={whoopIntegration} />;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Wearables" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                {page.props.flash?.success && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {page.props.flash.success}
                    </div>
                )}
                {page.props.flash?.status && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                        {page.props.flash.status}
                    </div>
                )}

                <WorkspaceHero
                    eyebrow="Wearables workspace"
                    title="Connection health and recovery signal come first."
                    description={`${scopeLabel}. This is where integration health, recovery snapshots, and ingest readiness stop being vague promises.`}
                    badges={[
                        whoopIntegration.oauthReady ? 'WHOOP OAuth ready' : 'WHOOP OAuth staged',
                        whoopIntegration.webhookReady ? 'Webhook signed' : 'Webhook missing',
                        `${summary.attentionRequired} needs attention`,
                    ]}
                    actions={
                        <>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/dashboard">Back to dashboard</Link>
                            </Button>
                            {whoopIntegration.oauthReady && (
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                    <Link href={whoopIntegration.connectUrl}>Connect WHOOP</Link>
                                </Button>
                            )}
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/api-access">Open API access</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">WHOOP links</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{whoopIntegration.connectedCount}</p>
                                <p className="mt-2 text-sm text-stone-600">{whoopIntegration.failingCount} link(s) currently need repair.</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Webhook queue</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{whoopIntegration.pendingEvents}</p>
                                <p className="mt-2 text-sm text-stone-600">{whoopIntegration.failedEvents} failed event(s) still need attention.</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Last webhook</p>
                                <p className="mt-3 text-sm font-semibold tracking-tight text-stone-950">
                                    {whoopIntegration.lastReceivedAt
                                        ? new Date(whoopIntegration.lastReceivedAt).toLocaleString()
                                        : 'No deliveries yet'}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">Keep this current or the dashboard gets blind fast.</p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Tracked connections"
                        value={summary.totalConnections.toString()}
                        note={`${connections.total} record(s) match the current filter.`}
                        icon={Watch}
                    />
                    <WorkspaceMetricCard
                        title="Healthy connections"
                        value={summary.healthyConnections.toString()}
                        note="Connected devices with recent ingest capacity."
                        icon={ShieldCheck}
                    />
                    <WorkspaceMetricCard
                        title="Needs attention"
                        value={summary.attentionRequired.toString()}
                        note="These relationships are most likely to become blind spots."
                        icon={AlertTriangle}
                    />
                    <WorkspaceMetricCard
                        title="Synced today"
                        value={summary.syncedToday.toString()}
                        note="Cron-safe MVPs still need fresh data, not excuses."
                        icon={Activity}
                    />
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Urgent"
                        title="Broken or stale connections belong at the top."
                        description="The review queue comes before the big directory because the broken stuff is what actually needs a decision."
                    />

                    <WorkspacePanel
                        title="Connection review queue"
                        description="The direct reasons a sync is stale, failing, or half-dead."
                        contentClassName="space-y-3"
                    >
                        <WorkspaceTable minWidth="min-w-[980px]">
                            <WorkspaceTableHeader labels={['User', 'Provider', 'Status', 'Severity', 'Stale', 'Failures', 'Issue', 'Next action']} />
                            {reviewQueue.length === 0 ? (
                                <WorkspaceTableEmpty message="Nothing is screaming for attention right now." colSpan={8} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {reviewQueue.map((entry) => (
                                        <tr key={entry.id} className="align-top transition-colors hover:bg-stone-50/80">
                                            <td className="px-4 py-4 font-semibold text-stone-950">{entry.userName}</td>
                                            <td className="px-4 py-4 text-sm text-stone-700">{entry.providerLabel}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForSeverity(entry.severity)}>{humanizeStatus(entry.severity)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-stone-700">
                                                {entry.staleHours !== null ? `${entry.staleHours}h` : 'N/A'}
                                            </td>
                                            <td className="px-4 py-4 text-sm text-stone-700">{entry.syncFailuresCount}</td>
                                            <td className="px-4 py-4">
                                                <p className="max-w-[18rem] text-sm leading-6 text-stone-700">{entry.issue}</p>
                                                {entry.lastErrorMessage && (
                                                    <p className="mt-1 line-clamp-2 max-w-[18rem] text-xs text-stone-500">
                                                        {entry.lastErrorMessage}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="max-w-[18rem] text-sm leading-6 text-stone-700">{entry.recommendation}</p>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Directory"
                        title="Filter the connection directory after the review queue."
                        description="The full directory still holds all the recovery and sync detail, but it no longer steals priority from broken links."
                    />

                    <WorkspacePanel
                        title="Connection directory"
                        description="Filter by health state and inspect the latest normalized recovery snapshot for each connection."
                        contentClassName="space-y-4"
                    >
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <p className="text-sm leading-6 text-stone-600">{connections.total} connection record(s) match the current filter.</p>
                            <div className="flex flex-wrap gap-2">
                                {statusFilters.map((filter) => (
                                    <Link
                                        key={filter.label}
                                        href={route('wearables.index', filter.value ? { status: filter.value } : {})}
                                        preserveScroll
                                        className={cn(
                                            'inline-flex items-center rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                            filters.status === filter.value || (!filters.status && filter.value === null)
                                                ? 'border-stone-900 bg-stone-900 text-white'
                                                : 'border-stone-200/80 bg-white text-stone-600 hover:border-stone-300 hover:text-stone-950',
                                        )}
                                    >
                                        {filter.label}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        <WorkspaceTable minWidth="min-w-[1320px]">
                            <WorkspaceTableHeader
                                labels={['User', 'Provider', 'Auth / scopes', 'Status', 'Last sync', 'Latest recovery', 'Trend', 'Review', 'Ingest']}
                            />
                            {connections.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No connections match this filter." colSpan={9} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {connections.data.map((connection) => (
                                        <tr key={connection.id} className="align-top transition-colors hover:bg-stone-50/80">
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-stone-950">{connection.userName}</p>
                                                <p className="mt-1 text-xs text-stone-500">{connection.userEmail}</p>
                                                {connection.userRole && (
                                                    <Badge variant="outline" className="mt-2">
                                                        {humanizeStatus(connection.userRole)}
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{connection.providerLabel}</p>
                                                <p className="mt-1 text-xs text-stone-500">{connection.publicId}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">{humanizeStatus(connection.authType)}</Badge>
                                                <p className="mt-2 line-clamp-2 max-w-[12rem] text-xs text-stone-500">
                                                    {connection.grantedScopes.length ? connection.grantedScopes.join(', ') : 'No scopes'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-col items-start gap-2">
                                                    <Badge variant={badgeVariantForStatus(connection.status)}>
                                                        {humanizeStatus(connection.status)}
                                                    </Badge>
                                                    <Badge variant={badgeVariantForSeverity(connection.review.severity)}>
                                                        {humanizeStatus(connection.review.severity)}
                                                    </Badge>
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="text-sm text-stone-700">{connection.lastSyncedAt ?? 'Never synced'}</p>
                                                <p className="mt-1 text-xs text-stone-500">
                                                    {connection.review.staleHours !== null ? `${connection.review.staleHours}h stale` : 'No stale data'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {connection.latestSnapshot ? (
                                                    <dl className="grid gap-1 text-xs text-stone-600">
                                                        <div className="flex justify-between gap-4">
                                                            <dt>Readiness</dt>
                                                            <dd className="font-medium text-stone-950">
                                                                {formatReadiness(connection.latestSnapshot.readinessScore)}
                                                            </dd>
                                                        </div>
                                                        <div className="flex justify-between gap-4">
                                                            <dt>Sleep</dt>
                                                            <dd className="font-medium text-stone-950">
                                                                {formatSleepHours(connection.latestSnapshot.sleepHours)}
                                                            </dd>
                                                        </div>
                                                        <div className="flex justify-between gap-4">
                                                            <dt>Strain</dt>
                                                            <dd className="font-medium text-stone-950">
                                                                {connection.latestSnapshot.strainScore ?? 'N/A'}
                                                            </dd>
                                                        </div>
                                                        <div className="flex justify-between gap-4">
                                                            <dt>HRV</dt>
                                                            <dd className="font-medium text-stone-950">
                                                                {connection.latestSnapshot.heartRateVariability ?? 'N/A'}
                                                            </dd>
                                                        </div>
                                                    </dl>
                                                ) : (
                                                    <span className="text-xs text-stone-500">No snapshot ingested</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <dl className="grid gap-1 text-xs text-stone-600">
                                                    <div className="flex justify-between gap-4">
                                                        <dt>Days</dt>
                                                        <dd className="font-medium text-stone-950">{connection.analytics.overview.daysTracked}</dd>
                                                    </div>
                                                    <div className="flex justify-between gap-4">
                                                        <dt>Avg readiness</dt>
                                                        <dd className="font-medium text-stone-950">
                                                            {formatReadiness(connection.analytics.overview.averageReadiness)}
                                                        </dd>
                                                    </div>
                                                    <div className="flex justify-between gap-4">
                                                        <dt>Avg sleep</dt>
                                                        <dd className="font-medium text-stone-950">
                                                            {formatSleepHours(connection.analytics.overview.averageSleepHours)}
                                                        </dd>
                                                    </div>
                                                </dl>
                                                {connection.analytics.alerts.length > 0 && (
                                                    <p className="mt-2 line-clamp-2 max-w-[12rem] text-xs text-stone-500">
                                                        {connection.analytics.alerts.join(', ')}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="max-w-[16rem] text-sm leading-6 text-stone-700">{connection.review.issue}</p>
                                                <p className="mt-1 line-clamp-2 max-w-[16rem] text-xs text-stone-500">
                                                    {connection.review.recommendation}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {connection.ingest ? (
                                                    <div className="space-y-1">
                                                        <p className="font-mono text-xs text-stone-700">****{connection.ingest.lastFour}</p>
                                                        <p className="line-clamp-2 max-w-[12rem] text-xs break-all text-stone-500">
                                                            {connection.ingest.path}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <Badge variant="outline">OAuth managed</Badge>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Advanced"
                        title="Keep the integration plumbing visible, just lower in the hierarchy."
                        description="Webhook URL, sample curl, sync commands, and capability detail still matter. They are simply not the first thing people should see."
                    />

                    <WorkspacePanel
                        title="WHOOP and ingest detail"
                        description="Provider-facing and backend-facing detail for operators and developers."
                        contentClassName="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]"
                    >
                        <div className="min-w-0 space-y-3 rounded-2xl border border-stone-200/75 bg-stone-50/70 p-4">
                            <div className="flex min-w-0 flex-wrap gap-2">
                                <Badge variant="outline">Lookback {whoopIntegration.lookbackDays} day(s)</Badge>
                                <Badge variant={whoopIntegration.webhookReady ? 'default' : 'secondary'}>
                                    {whoopIntegration.webhookReady ? 'Webhook signed' : 'Webhook missing'}
                                </Badge>
                                <Badge variant="outline">{whoopIntegration.pendingEvents} pending</Badge>
                                <Badge variant="outline">{whoopIntegration.failedEvents} failed</Badge>
                            </div>
                            <div className="space-y-2">
                                {whoopIntegration.capabilities.map((capability) => (
                                    <p key={capability} className="text-sm leading-6 text-stone-600">
                                        {capability}
                                    </p>
                                ))}
                            </div>
                        </div>

                        <div className="min-w-0 space-y-3">
                            <div className="min-w-0 rounded-2xl border border-dashed border-stone-200/80 bg-white/90 p-4">
                                <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Webhook URL</p>
                                <code className="mt-3 block text-xs leading-6 break-all text-stone-700">{whoopIntegration.webhookUrl}</code>
                            </div>
                            <div className="min-w-0 rounded-2xl border border-dashed border-stone-200/80 bg-white/90 p-4">
                                <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Fallback ingest</p>
                                <code className="mt-3 block text-xs leading-6 break-all whitespace-pre-wrap text-stone-700">{sampleCurl}</code>
                            </div>
                            <div className="grid min-w-0 gap-3 sm:grid-cols-2">
                                <div className="min-w-0 rounded-2xl border border-dashed border-stone-200/80 bg-white/90 p-4">
                                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Cron sync command</p>
                                    <code className="mt-3 block text-xs leading-6 break-all text-stone-700">{whoopIntegration.syncCommand}</code>
                                </div>
                                <div className="min-w-0 rounded-2xl border border-dashed border-stone-200/80 bg-white/90 p-4">
                                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Webhook processor</p>
                                    <code className="mt-3 block text-xs leading-6 break-all text-stone-700">
                                        {whoopIntegration.webhookProcessCommand}
                                    </code>
                                </div>
                            </div>
                        </div>
                    </WorkspacePanel>
                </section>

                <section className="text-sm text-stone-500">
                    <div className="flex items-center justify-between">
                        <span>
                            Page {connections.current_page} of {connections.last_page}
                        </span>
                        <div className="flex items-center gap-4">
                            {connections.prev_page_url ? (
                                <Link href={connections.prev_page_url} preserveScroll className="text-foreground font-medium">
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                            {connections.next_page_url ? (
                                <Link href={connections.next_page_url} preserveScroll className="text-foreground font-medium">
                                    Next
                                </Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
