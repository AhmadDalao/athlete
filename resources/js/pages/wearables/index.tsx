import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading, ReadinessDial, TrendBars } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
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
    whoopIntegration: {
        oauthReady: boolean;
        connectUrl: string;
        scopes: string[];
        lookbackDays: number;
        capabilities: string[];
        connectedCount: number;
        syncCommand: string;
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

function MetricCard({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: typeof Watch }) {
    return (
        <Card className="border-sidebar-border/70">
            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                    <CardDescription>{title}</CardDescription>
                    <CardTitle className="mt-3 text-3xl font-semibold tracking-tight">{value}</CardTitle>
                </div>
                <div className="bg-primary/10 text-primary rounded-full p-2">
                    <Icon className="size-4" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground text-sm leading-6">{note}</p>
            </CardContent>
        </Card>
    );
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

            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl bg-[linear-gradient(180deg,rgba(248,250,252,0.96),rgba(255,255,255,1))] p-4 md:p-6">
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
                                        <div className="rounded-xl border border-stone-200/75 bg-stone-50/80 p-4">
                                            <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                                Fallback ingest endpoint
                                            </p>
                                            <code className="mt-2 block text-xs leading-6 text-stone-700">{connection.ingest.path}</code>
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

export default function WearablesIndex({ viewerRole, scopeLabel, filters, summary, connections, whoopIntegration, sampleCurl }: WearablesPageProps) {
    if (viewerRole === 'athlete') {
        return <AthleteWearablesExperience summary={summary} connections={connections} whoopIntegration={whoopIntegration} />;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Wearables" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Card className="border-sidebar-border/70 from-background via-background to-muted/40 bg-linear-to-br">
                    <CardHeader>
                        <CardTitle className="text-3xl">Wearable control</CardTitle>
                        <CardDescription className="max-w-3xl leading-6">
                            {scopeLabel}. This is where integration health, recovery snapshots, and ingest credentials stop being vague promises.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 lg:grid-cols-[1.4fr_0.6fr]">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                title="Tracked connections"
                                value={summary.totalConnections.toString()}
                                note={`${connections.total} record(s) match the current filter.`}
                                icon={Watch}
                            />
                            <MetricCard
                                title="Healthy connections"
                                value={summary.healthyConnections.toString()}
                                note="Connected devices with recent ingest capacity."
                                icon={ShieldCheck}
                            />
                            <MetricCard
                                title="Needs attention"
                                value={summary.attentionRequired.toString()}
                                note="These are the device relationships most likely to become blind spots."
                                icon={AlertTriangle}
                            />
                            <MetricCard
                                title="Synced today"
                                value={summary.syncedToday.toString()}
                                note="Cron-safe MVPs still need fresh data, not just clever excuses."
                                icon={Activity}
                            />
                        </div>

                        <div className="border-sidebar-border/70 bg-muted/30 rounded-2xl border p-5">
                            <p className="text-muted-foreground text-sm font-medium">WHOOP integration</p>
                            <p className="mt-3 text-3xl font-semibold tracking-tight">{whoopIntegration.connectedCount}</p>
                            <p className="text-muted-foreground mt-3 text-sm leading-6">
                                WHOOP accounts connected right now. OAuth is {whoopIntegration.oauthReady ? 'configured' : 'not configured yet'}.
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {whoopIntegration.oauthReady && (
                                    <Link
                                        href={whoopIntegration.connectUrl}
                                        className="border-primary bg-primary text-primary-foreground inline-flex items-center rounded-full border px-3 py-1.5 text-sm font-medium"
                                    >
                                        Connect WHOOP
                                    </Link>
                                )}
                                <Badge variant="outline">Lookback {whoopIntegration.lookbackDays} day(s)</Badge>
                            </div>
                            <div className="mt-4 space-y-2">
                                {whoopIntegration.capabilities.map((capability) => (
                                    <p key={capability} className="text-muted-foreground text-sm leading-6">
                                        {capability}
                                    </p>
                                ))}
                            </div>
                            {(viewerRole === 'admin' || viewerRole === 'athlete') && (
                                <div className="border-sidebar-border/70 bg-background/80 mt-4 rounded-xl border border-dashed p-3">
                                    <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Fallback ingest</p>
                                    <code className="mt-2 block text-xs leading-6">{sampleCurl}</code>
                                    <p className="text-muted-foreground mt-2 text-xs leading-5">Cron sync command: {whoopIntegration.syncCommand}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <section className="space-y-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="text-xl font-semibold tracking-tight">Connection queue</h2>
                            <p className="text-muted-foreground text-sm leading-6">
                                Filter by health state and inspect the latest normalized recovery snapshot for each connection.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {statusFilters.map((filter) => (
                                <Link
                                    key={filter.label}
                                    href={route('wearables.index', filter.value ? { status: filter.value } : {})}
                                    preserveScroll
                                    className={cn(
                                        'inline-flex items-center rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                        filters.status === filter.value || (!filters.status && filter.value === null)
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-sidebar-border/70 bg-background text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {filter.label}
                                </Link>
                            ))}
                        </div>
                    </div>

                    <Card className="border-sidebar-border/70">
                        <CardContent className="space-y-3 p-4">
                            {connections.data.length === 0 ? (
                                <div className="border-sidebar-border/70 rounded-xl border border-dashed p-8 text-center">
                                    <p className="font-medium">No connections match this filter.</p>
                                    <p className="text-muted-foreground mt-2 text-sm">That is either good ops or a very lonely demo dataset.</p>
                                </div>
                            ) : (
                                connections.data.map((connection) => (
                                    <div
                                        key={connection.id}
                                        className="border-sidebar-border/70 hover:bg-muted/30 rounded-2xl border p-4 transition-colors"
                                    >
                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div className="space-y-1">
                                                <p className="font-medium">{connection.userName}</p>
                                                <p className="text-muted-foreground text-sm">{connection.userEmail}</p>
                                                <div className="flex flex-wrap gap-2 pt-1">
                                                    {connection.userRole && <Badge variant="outline">{humanizeStatus(connection.userRole)}</Badge>}
                                                    <Badge variant="outline">{connection.providerLabel}</Badge>
                                                    <Badge variant="outline">{humanizeStatus(connection.authType)}</Badge>
                                                    {connection.grantedScopes.map((scope) => (
                                                        <Badge key={scope} variant="outline">
                                                            {scope}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant={badgeVariantForStatus(connection.status)}>{humanizeStatus(connection.status)}</Badge>
                                                <Badge variant="outline">{connection.lastSyncedAt ?? 'Never synced'}</Badge>
                                            </div>
                                        </div>

                                        {connection.latestSnapshot ? (
                                            <div className="mt-4 grid gap-3 md:grid-cols-4">
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Readiness</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {formatReadiness(connection.latestSnapshot.readinessScore)}
                                                    </p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Sleep</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {formatSleepHours(connection.latestSnapshot.sleepHours)}
                                                    </p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Strain</p>
                                                    <p className="mt-2 text-sm font-medium">{connection.latestSnapshot.strainScore ?? 'N/A'}</p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Sleep debt</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {formatSleepDebt(connection.latestSnapshot.sleepDebtHours)}
                                                    </p>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="border-sidebar-border/70 mt-4 rounded-xl border border-dashed p-4">
                                                <p className="text-muted-foreground text-sm">
                                                    No normalized snapshot has been ingested for this connection yet.
                                                </p>
                                            </div>
                                        )}

                                        {connection.latestSnapshot && (
                                            <div className="mt-4 grid gap-3 md:grid-cols-3">
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">HRV</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {connection.latestSnapshot.heartRateVariability ?? 'N/A'}
                                                    </p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Resting HR</p>
                                                    <p className="mt-2 text-sm font-medium">{connection.latestSnapshot.restingHeartRate ?? 'N/A'}</p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Training load</p>
                                                    <p className="mt-2 text-sm font-medium">{connection.latestSnapshot.trainingLoad ?? 'N/A'}</p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Sleep performance</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {formatPercentage(connection.latestSnapshot.sleepPerformancePercentage)}
                                                    </p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Sleep consistency</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {formatPercentage(connection.latestSnapshot.sleepConsistencyPercentage)}
                                                    </p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Blood oxygen</p>
                                                    <p className="mt-2 text-sm font-medium">
                                                        {connection.latestSnapshot.bloodOxygenPercent === null
                                                            ? 'N/A'
                                                            : `${connection.latestSnapshot.bloodOxygenPercent}%`}
                                                    </p>
                                                </div>
                                            </div>
                                        )}

                                        {connection.analytics.overview.daysTracked > 0 && (
                                            <div className="border-sidebar-border/70 mt-4 rounded-xl border p-4">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <p className="text-sm font-medium">Trend view</p>
                                                    <Badge variant="outline">{connection.analytics.overview.daysTracked} tracked day(s)</Badge>
                                                </div>
                                                <div className="mt-3 grid gap-3 md:grid-cols-4">
                                                    <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Avg readiness</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatReadiness(connection.analytics.overview.averageReadiness)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Avg sleep</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatSleepHours(connection.analytics.overview.averageSleepHours)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Avg HRV</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {connection.analytics.overview.averageHrv ?? 'N/A'}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Sleep debt</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatSleepDebt(connection.analytics.overview.averageSleepDebtHours)}
                                                        </p>
                                                    </div>
                                                </div>
                                                {connection.analytics.alerts.length > 0 && (
                                                    <div className="mt-3 flex flex-wrap gap-2">
                                                        {connection.analytics.alerts.map((alert) => (
                                                            <Badge key={alert} variant="secondary">
                                                                {alert}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                )}
                                                <div className="mt-4 grid gap-2">
                                                    {connection.analytics.timeline.map((entry) => (
                                                        <div
                                                            key={entry.metricDate ?? 'unknown-metric-date'}
                                                            className="border-sidebar-border/70 text-muted-foreground grid gap-2 rounded-xl border p-3 text-sm md:grid-cols-4"
                                                        >
                                                            <p className="text-foreground font-medium">{entry.metricDate ?? 'Unknown date'}</p>
                                                            <p>Readiness {formatReadiness(entry.readinessScore)}</p>
                                                            <p>Sleep {formatSleepHours(entry.sleepHours)}</p>
                                                            <p>Load {entry.trainingLoad ?? 'N/A'}</p>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {connection.ingest && (
                                            <div className="mt-4 grid gap-3 lg:grid-cols-[0.8fr_1.2fr]">
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Ingest key</p>
                                                    <code className="mt-2 block text-sm">{connection.ingest.key}</code>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Endpoint</p>
                                                    <code className="mt-2 block text-sm">{connection.ingest.path}</code>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <div className="text-muted-foreground flex items-center justify-between text-sm">
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
