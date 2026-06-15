import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading, ReadinessDial, TrendBars } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Beef,
    Clock3,
    CreditCard,
    Droplets,
    Dumbbell,
    Flame,
    HeartPulse,
    LineChart,
    MoonStar,
    Scale,
    Shield,
    TrendingUp,
    Users,
    Watch,
    type LucideIcon,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface ViewerRole {
    name: string;
    label: string;
}

interface Viewer {
    name: string;
    email: string;
    phone: string | null;
    primaryGoal: string | null;
    preferredContactMethod: string | null;
    registrationChannel: string | null;
    roles: ViewerRole[];
    primaryRole: string | null;
}

interface AdminOverview {
    metrics: {
        totalUsers: number;
        totalCoaches: number;
        totalAthletes: number;
        activeMemberships: number;
        expiringMemberships: number;
        connectedDevices: number;
        attentionConnections: number;
        projectedMonthlyRevenue: number;
        coachPayoutLiability: number;
        activePrograms: number;
        scheduledSessionsThisWeek: number;
        loggedSessionsThisWeek: number;
        paymentVolumeThisMonth: number;
        failedPaymentsThisMonth: number;
        newUsersThisMonth: number;
    };
    expiringMembershipWatchlist: Array<{
        userName: string;
        planName: string;
        status: string;
        daysRemaining: number | null;
        endsAt: string | null;
    }>;
    deviceAttentionQueue: Array<{
        userName: string;
        provider: string;
        status: string;
        lastSyncedAt: string | null;
    }>;
    paymentAttentionQueue: Array<{
        userName: string;
        planName: string;
        status: string;
        amount: number | null;
        currency: string;
        eventAt: string | null;
        reference: string | null;
        notes: string | null;
    }>;
    signupMix: Array<{
        method: string;
        label: string;
        enabled: boolean;
        count: number;
    }>;
}

interface CoachOverview {
    metrics: {
        rosterCount: number;
        athletesNeedingAttention: number;
        activePrograms: number;
        upcomingSessions: number;
        pendingWorkoutLogs: number;
    };
    roster: Array<{
        id: number;
        name: string;
        email: string;
        goal: string;
        membershipStatus: string;
        membershipPlan: string;
        daysRemaining: number | null;
        connectedDevices: number;
        latestSnapshot: {
            metricDate: string | null;
            readinessScore: number | null;
            sleepHours: number | null;
            strainScore: number | null;
        } | null;
        latestCheckIn: {
            loggedDate: string | null;
            weightKg: number | null;
            caloriesConsumed: number | null;
            proteinGrams: number | null;
            waterLiters: number | null;
            energyScore: number | null;
            sorenessScore: number | null;
        } | null;
        currentProgram: {
            title: string;
            status: string;
            nextSessionDate: string | null;
            pendingLogs: number;
            lastWorkoutStatus: string | null;
        } | null;
        flags: string[];
    }>;
    attentionQueue: Array<{
        id: number;
        name: string;
        goal: string;
        flags: string[];
        currentProgram: {
            title: string;
            status: string;
            nextSessionDate: string | null;
            pendingLogs: number;
            lastWorkoutStatus: string | null;
        } | null;
    }>;
    upcomingSessions: Array<{
        athleteName: string;
        programTitle: string;
        sessionTitle: string;
        scheduledDate: string | null;
        focus: string | null;
    }>;
}

interface ExerciseRow {
    name: string;
    prescription: string | null;
    sets: number | null;
    reps: string | null;
    load: string | null;
    rest_seconds: number | null;
    rest_label: string | null;
    target: string | null;
    note: string | null;
}

interface MetricTimelineEntry {
    metricDate: string | null;
    readinessScore: number | null;
    readinessBand: string | null;
    strainScore: number | null;
    sleepHours: number | null;
    sleepNeedHours: number | null;
    sleepDebtHours: number | null;
    steps: number | null;
    distanceMeters: number | null;
    activeMinutes: number | null;
    restingHeartRate: number | null;
    heartRateVariability: number | null;
    trainingLoad: number | null;
    sleepPerformancePercentage: number | null;
    sleepConsistencyPercentage: number | null;
    sleepEfficiencyPercentage: number | null;
    respiratoryRate: number | null;
    bloodOxygenPercent: number | null;
    skinTemperatureCelsius: number | null;
    remSleepHours: number | null;
    slowWaveSleepHours: number | null;
}

interface MetricReport {
    latest: MetricTimelineEntry | null;
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
    timeline: MetricTimelineEntry[];
    alerts: string[];
}

interface ProgressTimelineEntry {
    id: number;
    loggedDate: string | null;
    weightKg: number | null;
    bodyFatPercentage: number | null;
    waistCm: number | null;
    caloriesConsumed: number | null;
    proteinGrams: number | null;
    carbsGrams: number | null;
    fatGrams: number | null;
    waterLiters: number | null;
    mealsLoggedCount: number | null;
    energyScore: number | null;
    sorenessScore: number | null;
    stressScore: number | null;
    sleepQualityScore: number | null;
    notes: string | null;
}

interface ProgressReport {
    latest: ProgressTimelineEntry | null;
    overview: {
        daysTracked: number;
        checkInsThisWeek: number;
        lastLoggedDate: string | null;
        latestWeightKg: number | null;
        weightDeltaKg: number | null;
        averageCaloriesConsumed: number | null;
        averageProteinGrams: number | null;
        averageCarbsGrams: number | null;
        averageFatGrams: number | null;
        averageWaterLiters: number | null;
        averageEnergyScore: number | null;
        averageSorenessScore: number | null;
        averageStressScore: number | null;
        averageSleepQualityScore: number | null;
        averageBodyFatPercentage: number | null;
        averageWaistCm: number | null;
        scheduledSessions: number;
        completedSessions: number;
        completionRate: number | null;
    };
    timeline: ProgressTimelineEntry[];
    alerts: string[];
}

interface AthleteOverview {
    metrics: {
        coachCount: number;
        connectedDevices: number;
        membershipDaysRemaining: number | null;
        latestReadinessScore: number | null;
        latestWeightKg: number | null;
        weightDeltaKg: number | null;
        averageCaloriesConsumed: number | null;
        averageProteinGrams: number | null;
        checkInsThisWeek: number;
        upcomingSessionsCount: number;
        completedSessionsThisWeek: number;
    };
    membership: {
        planName: string;
        status: string;
        daysRemaining: number | null;
        renewsAt: string | null;
        endsAt: string | null;
        autoRenew: boolean;
    };
    training: {
        title: string;
        goal: string | null;
        status: string;
        coachName: string;
        startDate: string | null;
        endDate: string | null;
        nextSession: {
            title: string;
            scheduledDate: string | null;
            focus: string | null;
            instructions: string | null;
            exercises: ExerciseRow[];
        } | null;
        upcomingSessions: Array<{
            title: string;
            scheduledDate: string | null;
            focus: string | null;
            exerciseCount: number;
        }>;
        recentLogs: Array<{
            sessionTitle: string;
            completionStatus: string;
            performedAt: string | null;
            durationMinutes: number | null;
            exertionRating: number | null;
        }>;
    } | null;
    coaches: Array<{
        id: number;
        name: string;
        email: string;
        relationshipStatus: string;
        startDate: string | null;
        goal: string | null;
    }>;
    deviceConnections: Array<{
        provider: string;
        providerLabel: string;
        status: string;
        lastSyncedAt: string | null;
        latestSnapshot: {
            metricDate: string | null;
            readinessScore: number | null;
            sleepHours: number | null;
            strainScore: number | null;
        } | null;
    }>;
    latestSnapshot: {
        metricDate: string | null;
        readinessScore: number | null;
        strainScore: number | null;
        sleepHours: number | null;
        sleepNeedHours: number | null;
        sleepDebtHours: number | null;
        steps: number | null;
        restingHeartRate: number | null;
        heartRateVariability: number | null;
        sleepPerformancePercentage: number | null;
        sleepConsistencyPercentage: number | null;
        respiratoryRate: number | null;
        bloodOxygenPercent: number | null;
        skinTemperatureCelsius: number | null;
        trainingLoad: number | null;
    } | null;
    latestCheckIn: ProgressTimelineEntry | null;
    metricReport: MetricReport;
    progressReport: ProgressReport;
}

interface DashboardPageProps {
    viewer: Viewer;
    admin: AdminOverview | null;
    coach: CoachOverview | null;
    athlete: AthleteOverview | null;
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
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

function formatReadiness(score: number | null) {
    if (score === null) {
        return 'No readiness data';
    }

    return `${Math.round(score)}/100`;
}

function formatSleepHours(hours: number | null) {
    if (hours === null) {
        return 'No sleep data';
    }

    return `${hours.toFixed(1)}h`;
}

function formatSleepDebt(hours: number | null) {
    if (hours === null) {
        return 'No sleep-need data';
    }

    if (hours <= 0) {
        return `${Math.abs(hours).toFixed(1)}h banked`;
    }

    return `${hours.toFixed(1)}h behind`;
}

function formatSignedDelta(value: number | null) {
    if (value === null) {
        return 'No change data';
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value.toFixed(1)}`;
}

function formatPercentage(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${Math.round(value)}%`;
}

function formatWeight(value: number | null) {
    if (value === null) {
        return 'No weigh-in';
    }

    return `${value.toFixed(1)} kg`;
}

function formatCalories(value: number | null) {
    if (value === null) {
        return 'No calorie log';
    }

    return `${Math.round(value)} kcal`;
}

function formatGrams(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${Math.round(value)} g`;
}

function formatLiters(value: number | null) {
    if (value === null) {
        return 'N/A';
    }

    return `${value.toFixed(1)} L`;
}

function formatRestLabel(seconds: number | null, label: string | null) {
    if (label) {
        return label;
    }

    if (seconds === null) {
        return null;
    }

    if (seconds >= 60 && seconds % 60 === 0) {
        return `${seconds / 60} min`;
    }

    return `${seconds}s`;
}

function badgeVariantForStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['past_due', 'cancelled', 'expired', 'disconnected', 'missed'].includes(status)) {
        return 'destructive';
    }

    if (['grace', 'attention', 'none', 'partial', 'draft'].includes(status)) {
        return 'secondary';
    }

    if (['trialing', 'active', 'connected', 'completed'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function exerciseHeadline(exercise: ExerciseRow) {
    if (exercise.sets && exercise.reps) {
        return `${exercise.sets} x ${exercise.reps}`;
    }

    return exercise.prescription;
}

function MetricCard({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: LucideIcon }) {
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

function QuickLinkCard({ title, href, note, icon: Icon }: { title: string; href: string; note: string; icon: LucideIcon }) {
    return (
        <Link href={href} className="block">
            <Card className="border-sidebar-border/70 hover:bg-muted/30 h-full transition-colors">
                <CardHeader className="flex flex-row items-start justify-between space-y-0">
                    <div>
                        <CardTitle className="text-lg">{title}</CardTitle>
                        <CardDescription className="mt-2 leading-6">{note}</CardDescription>
                    </div>
                    <div className="bg-primary/10 text-primary rounded-full p-2">
                        <Icon className="size-4" />
                    </div>
                </CardHeader>
                <CardContent className="text-primary flex items-center text-sm font-medium">
                    Open view
                    <ArrowRight className="ml-2 size-4" />
                </CardContent>
            </Card>
        </Link>
    );
}

function ExerciseChipCard({ exercise }: { exercise: ExerciseRow }) {
    const restLabel = formatRestLabel(exercise.rest_seconds, exercise.rest_label);

    return (
        <div className="border-sidebar-border/70 rounded-xl border p-3">
            <p className="font-medium">{exercise.name}</p>
            {exerciseHeadline(exercise) && <p className="text-muted-foreground mt-2 text-sm">{exerciseHeadline(exercise)}</p>}
            <div className="mt-3 flex flex-wrap gap-2">
                {exercise.load && <Badge variant="outline">Load {exercise.load}</Badge>}
                {restLabel && <Badge variant="outline">Rest {restLabel}</Badge>}
                {exercise.target && <Badge variant="outline">{exercise.target}</Badge>}
            </div>
            {exercise.note && <p className="text-muted-foreground mt-3 text-sm leading-6">{exercise.note}</p>}
        </div>
    );
}

function shortDayLabel(value: string | null) {
    if (!value) {
        return 'N/A';
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

function AthleteDashboardExperience({ viewer, athlete }: { viewer: Viewer; athlete: AthleteOverview }) {
    const readinessTimeline = athlete.metricReport.timeline.slice(-7).map((entry) => ({
        label: shortDayLabel(entry.metricDate),
        value: entry.readinessScore,
        note: entry.readinessBand ? entry.readinessBand.toUpperCase() : undefined,
    }));

    const sleepTimeline = athlete.metricReport.timeline.slice(-7).map((entry) => ({
        label: shortDayLabel(entry.metricDate),
        value: entry.sleepHours,
        note: entry.sleepNeedHours === null ? undefined : `${entry.sleepNeedHours.toFixed(1)}h need`,
    }));

    const latestSnapshot = athlete.latestSnapshot;
    const nextSession = athlete.training?.nextSession;
    const completedLogs = athlete.training?.recentLogs.filter((log) => log.completionStatus === 'completed').length ?? 0;
    const recentLogCount = athlete.training?.recentLogs.length ?? 0;
    const complianceValue = recentLogCount === 0 ? 'No logs yet' : `${Math.round((completedLogs / recentLogCount) * 100)}%`;
    const heroBadges = [
        athlete.membership.planName,
        humanizeStatus(athlete.membership.status),
        `${athlete.metrics.coachCount} coach${athlete.metrics.coachCount === 1 ? '' : 'es'}`,
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl bg-[linear-gradient(180deg,rgba(248,250,252,0.96),rgba(255,255,255,1))] p-4 md:p-6">
                <AthleteHero
                    eyebrow="Athlete command board"
                    title={`Your work is clear, ${viewer.name}.`}
                    description="Recovery, training, membership runway, and coach visibility now live in one place. No vague plan PDFs, no guessing if today should be pushed or backed off."
                    badges={heroBadges}
                    actions={
                        <>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/training">Open training</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/wearables">Open recovery</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/progress">Open progress</Link>
                            </Button>
                        </>
                    }
                >
                    <div className="space-y-4">
                        <ReadinessDial
                            score={athlete.metrics.latestReadinessScore}
                            label={latestSnapshot?.metricDate ? `Latest sync · ${shortDayLabel(latestSnapshot.metricDate)}` : 'Latest sync'}
                            note={formatReadiness(athlete.metrics.latestReadinessScore)}
                            detail={
                                latestSnapshot
                                    ? `Sleep ${formatSleepHours(latestSnapshot.sleepHours)} · strain ${latestSnapshot.strainScore ?? 'N/A'} · debt ${formatSleepDebt(latestSnapshot.sleepDebtHours)}`
                                    : 'Connect a device and let the data do its job.'
                            }
                        />
                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Membership runway</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {athlete.membership.daysRemaining === null ? 'Open end' : formatDays(athlete.membership.daysRemaining)}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {athlete.membership.autoRenew ? 'Auto renew is on.' : 'Manual renewal.'}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Next session</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{nextSession?.title ?? 'Not scheduled'}</p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {nextSession?.scheduledDate ? shortDayLabel(nextSession.scheduledDate) : 'Coach still needs to set the date.'}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Coach contact</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{athlete.coaches[0]?.name ?? 'No coach'}</p>
                                <p className="mt-2 text-sm text-stone-600">{athlete.coaches[0]?.email ?? 'Assignment pending.'}</p>
                            </div>
                        </div>
                    </div>
                </AthleteHero>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <AthleteMetricCard
                        title="Days remaining"
                        value={athlete.metrics.membershipDaysRemaining === null ? 'N/A' : athlete.metrics.membershipDaysRemaining.toString()}
                        note={`Current access is ${humanizeStatus(athlete.membership.status)}.`}
                        icon={CreditCard}
                        tone="amber"
                    />
                    <AthleteMetricCard
                        title="Latest readiness"
                        value={formatReadiness(athlete.metrics.latestReadinessScore)}
                        note={`${athlete.metricReport.overview.lowReadinessDays} low-readiness day(s) in the current trend window.`}
                        icon={HeartPulse}
                    />
                    <AthleteMetricCard
                        title="Current weight"
                        value={formatWeight(athlete.metrics.latestWeightKg)}
                        note={`Window delta ${athlete.metrics.weightDeltaKg === null ? 'N/A' : `${formatSignedDelta(athlete.metrics.weightDeltaKg)} kg`}.`}
                        icon={Scale}
                        tone="amber"
                    />
                    <AthleteMetricCard
                        title="Average calories"
                        value={formatCalories(athlete.metrics.averageCaloriesConsumed)}
                        note={`${athlete.metrics.checkInsThisWeek} manual check-in(s) logged this week.`}
                        icon={Flame}
                        tone="amber"
                    />
                    <AthleteMetricCard
                        title="Average protein"
                        value={formatGrams(athlete.metrics.averageProteinGrams)}
                        note="Simple recovery anchor. Weak protein usually means a weak week."
                        icon={Beef}
                    />
                    <AthleteMetricCard
                        title="Sleep debt"
                        value={formatSleepDebt(latestSnapshot?.sleepDebtHours ?? athlete.metricReport.overview.averageSleepDebtHours)}
                        note="Bank sleep when you can. Borrowing it forever is dumb."
                        icon={MoonStar}
                        tone="stone"
                    />
                    <AthleteMetricCard
                        title="Recent compliance"
                        value={complianceValue}
                        note={`${athlete.metrics.completedSessionsThisWeek} completed session(s) this week.`}
                        icon={TrendingUp}
                    />
                    <AthleteMetricCard
                        title="Hydration trend"
                        value={formatLiters(athlete.progressReport.overview.averageWaterLiters)}
                        note={`Average energy ${athlete.progressReport.overview.averageEnergyScore === null ? 'N/A' : `${athlete.progressReport.overview.averageEnergyScore}/10`}.`}
                        icon={Droplets}
                    />
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Recovery"
                        title="Signals that tell you whether today should hit hard or smart."
                        description="The point is not collecting data for its own sake. The point is using it before bad decisions pile up."
                    />
                    <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                        <AthletePanel
                            title="Seven-day recovery rhythm"
                            description="Readiness and sleep trend together. When both drag, pretending you feel great does not count."
                            contentClassName="space-y-4"
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <TrendBars
                                    title="Readiness trend"
                                    description="A quick read on how prepared your body has been."
                                    points={readinessTimeline}
                                    formatter={(value) => (value === null ? 'N/A' : `${Math.round(value)}`)}
                                    tone="teal"
                                />
                                <TrendBars
                                    title="Sleep trend"
                                    description="Hours slept against the recent need signal."
                                    points={sleepTimeline}
                                    formatter={(value) => (value === null ? 'N/A' : `${value.toFixed(1)}h`)}
                                    tone="amber"
                                />
                            </div>
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Resting HR</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">
                                        {latestSnapshot?.restingHeartRate === null || latestSnapshot?.restingHeartRate === undefined
                                            ? 'N/A'
                                            : `${latestSnapshot.restingHeartRate} bpm`}
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
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Respiratory</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">
                                        {latestSnapshot?.respiratoryRate === null || latestSnapshot?.respiratoryRate === undefined
                                            ? 'N/A'
                                            : `${latestSnapshot.respiratoryRate.toFixed(1)} rpm`}
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Sleep performance</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">
                                        {formatPercentage(latestSnapshot?.sleepPerformancePercentage ?? null)}
                                    </p>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {athlete.metricReport.alerts.length === 0 ? (
                                    <Badge variant="outline">No recovery alerts in the current trend window.</Badge>
                                ) : (
                                    athlete.metricReport.alerts.map((alert) => (
                                        <Badge key={alert} variant="secondary">
                                            {alert}
                                        </Badge>
                                    ))
                                )}
                            </div>
                        </AthletePanel>

                        <AthletePanel
                            title="Device loop"
                            description="Connected wearables and the last signal each one delivered."
                            contentClassName="space-y-3"
                        >
                            {athlete.deviceConnections.length === 0 ? (
                                <p className="text-sm leading-6 text-stone-500">
                                    No device connected yet. That kills the recovery side of the product.
                                </p>
                            ) : (
                                athlete.deviceConnections.map((connection) => (
                                    <div
                                        key={`${connection.provider}-${connection.lastSyncedAt ?? 'never'}`}
                                        className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4"
                                    >
                                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p className="font-medium text-stone-950">{connection.providerLabel}</p>
                                                <p className="text-sm text-stone-500">
                                                    {connection.latestSnapshot?.metricDate
                                                        ? `Latest day ${shortDayLabel(connection.latestSnapshot.metricDate)}`
                                                        : 'No metric day yet'}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant={badgeVariantForStatus(connection.status)}>{humanizeStatus(connection.status)}</Badge>
                                                <Badge variant="outline">
                                                    {connection.latestSnapshot?.readinessScore === null ||
                                                    connection.latestSnapshot?.readinessScore === undefined
                                                        ? 'No readiness'
                                                        : `${Math.round(connection.latestSnapshot.readinessScore)}/100`}
                                                </Badge>
                                            </div>
                                        </div>
                                        <p className="mt-3 text-sm leading-6 text-stone-600">
                                            {connection.lastSyncedAt ?? 'Never synced'} · Sleep{' '}
                                            {formatSleepHours(connection.latestSnapshot?.sleepHours ?? null)} · Strain{' '}
                                            {connection.latestSnapshot?.strainScore ?? 'N/A'}
                                        </p>
                                    </div>
                                ))
                            )}
                        </AthletePanel>
                    </div>
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Fuel and body"
                        title="Manual progress data the wearable cannot guess."
                        description="Weight, food intake, hydration, soreness, and stress are part of the coaching loop. Pretending otherwise is lazy."
                    />
                    <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                        <AthletePanel
                            title="Seven-day support trend"
                            description="This is the part that tells you whether the body is being supported well enough to handle the plan."
                            contentClassName="space-y-4"
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <TrendBars
                                    title="Weight trend"
                                    description="Short-term bodyweight trend across the latest manual logs."
                                    points={athlete.progressReport.timeline.slice(-7).map((entry) => ({
                                        label: shortDayLabel(entry.loggedDate),
                                        value: entry.weightKg,
                                        note: entry.weightKg === null ? 'No weigh-in' : `${entry.weightKg.toFixed(1)} kg`,
                                    }))}
                                    formatter={(value) => (value === null ? 'N/A' : value.toFixed(1))}
                                    tone="amber"
                                />
                                <TrendBars
                                    title="Protein trend"
                                    description="Protein intake is a brutally honest proxy for whether recovery support is slipping."
                                    points={athlete.progressReport.timeline.slice(-7).map((entry) => ({
                                        label: shortDayLabel(entry.loggedDate),
                                        value: entry.proteinGrams,
                                        note: entry.proteinGrams === null ? 'No protein log' : `${entry.proteinGrams} g`,
                                    }))}
                                    formatter={(value) => (value === null ? 'N/A' : `${Math.round(value)}g`)}
                                    tone="teal"
                                />
                            </div>
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Check-ins</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">{athlete.progressReport.overview.daysTracked}</p>
                                    <p className="mt-1 text-sm text-stone-600">Tracked day(s) in the current window.</p>
                                </div>
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Hydration</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">
                                        {formatLiters(athlete.progressReport.overview.averageWaterLiters)}
                                    </p>
                                    <p className="mt-1 text-sm text-stone-600">Average daily water logged.</p>
                                </div>
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Energy</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">
                                        {athlete.progressReport.overview.averageEnergyScore === null
                                            ? 'N/A'
                                            : `${athlete.progressReport.overview.averageEnergyScore}/10`}
                                    </p>
                                    <p className="mt-1 text-sm text-stone-600">Average subjective energy.</p>
                                </div>
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Sleep quality</p>
                                    <p className="mt-2 text-lg font-semibold text-stone-950">
                                        {athlete.progressReport.overview.averageSleepQualityScore === null
                                            ? 'N/A'
                                            : `${athlete.progressReport.overview.averageSleepQualityScore}/10`}
                                    </p>
                                    <p className="mt-1 text-sm text-stone-600">Manual check-in score, not device output.</p>
                                </div>
                            </div>
                        </AthletePanel>

                        <AthletePanel
                            title="Latest manual check-in"
                            description="The freshest body and nutrition context next to the recovery signal."
                            contentClassName="space-y-3"
                        >
                            {athlete.latestCheckIn ? (
                                <>
                                    <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                        <p className="font-medium text-stone-950">{shortDayLabel(athlete.latestCheckIn.loggedDate)}</p>
                                        <p className="mt-2 text-sm text-stone-600">
                                            {formatWeight(athlete.latestCheckIn.weightKg)} · {formatCalories(athlete.latestCheckIn.caloriesConsumed)}{' '}
                                            · {formatGrams(athlete.latestCheckIn.proteinGrams)}
                                        </p>
                                        <p className="mt-1 text-sm text-stone-600">
                                            Water {formatLiters(athlete.latestCheckIn.waterLiters)} · Soreness{' '}
                                            {athlete.latestCheckIn.sorenessScore === null ? 'N/A' : `${athlete.latestCheckIn.sorenessScore}/10`} ·
                                            Energy {athlete.latestCheckIn.energyScore === null ? 'N/A' : `${athlete.latestCheckIn.energyScore}/10`}
                                        </p>
                                        {athlete.latestCheckIn.notes && (
                                            <p className="mt-3 text-sm leading-6 text-stone-700">{athlete.latestCheckIn.notes}</p>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {athlete.progressReport.alerts.length === 0 ? (
                                            <Badge variant="outline">No manual progress alerts in the current window.</Badge>
                                        ) : (
                                            athlete.progressReport.alerts.map((alert) => (
                                                <Badge key={alert} variant="secondary">
                                                    {alert}
                                                </Badge>
                                            ))
                                        )}
                                    </div>
                                    <Button asChild variant="outline" className="w-full justify-between">
                                        <Link href="/progress">
                                            Open full progress board
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                </>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-stone-200/80 p-5 text-sm leading-6 text-stone-600">
                                    No manual progress check-in has been logged yet. That leaves a pretty obvious blind spot around food, weight,
                                    hydration, and soreness.
                                </div>
                            )}
                        </AthletePanel>
                    </div>
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Training"
                        title="Your block, your next session, and the proof you actually did the work."
                        description="The coach assigns the plan. You should be able to read it fast, execute it cleanly, and log it without friction."
                    />
                    <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                        <AthletePanel
                            title={athlete.training?.title ?? 'No active block'}
                            description={
                                athlete.training
                                    ? `${athlete.training.coachName} is driving this block. ${athlete.training.goal ?? 'No written goal yet.'}`
                                    : 'No training block is assigned yet.'
                            }
                            contentClassName="space-y-5"
                        >
                            {athlete.training ? (
                                <>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant={badgeVariantForStatus(athlete.training.status)}>
                                            {humanizeStatus(athlete.training.status)}
                                        </Badge>
                                        <Badge variant="outline">
                                            {athlete.training.startDate ?? 'No start'} to {athlete.training.endDate ?? 'Open end'}
                                        </Badge>
                                    </div>

                                    {nextSession ? (
                                        <div className="rounded-[1.6rem] border border-stone-200/80 bg-[linear-gradient(135deg,rgba(255,247,237,0.8),rgba(240,253,250,0.74))] p-5">
                                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div>
                                                    <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">
                                                        Next session
                                                    </p>
                                                    <h3 className="mt-2 text-2xl font-semibold tracking-tight text-stone-950">{nextSession.title}</h3>
                                                    <p className="mt-2 text-sm text-stone-600">
                                                        {nextSession.scheduledDate ? shortDayLabel(nextSession.scheduledDate) : 'Date not scheduled'}
                                                        {nextSession.focus ? ` · ${nextSession.focus}` : ''}
                                                    </p>
                                                    {nextSession.instructions && (
                                                        <p className="mt-4 max-w-2xl text-sm leading-6 text-stone-600">{nextSession.instructions}</p>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2 rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-medium text-stone-800">
                                                    <Clock3 className="size-4" />
                                                    {nextSession.exercises.length} exercise{nextSession.exercises.length === 1 ? '' : 's'}
                                                </div>
                                            </div>
                                            <div className="mt-5 grid gap-3 md:grid-cols-2">
                                                {nextSession.exercises.map((exercise) => (
                                                    <ExerciseChipCard key={`${nextSession.title}-${exercise.name}`} exercise={exercise} />
                                                ))}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-5 text-sm leading-6 text-stone-500">
                                            No next session is scheduled yet. The block exists, but the calendar still needs work.
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-5 text-sm leading-6 text-stone-500">
                                    No active block yet. Once a coach assigns one, this page becomes the athlete’s actual training source of truth.
                                </div>
                            )}
                        </AthletePanel>

                        <div className="grid gap-4">
                            <AthletePanel
                                title="Upcoming sessions"
                                description="What the next stretch of work looks like."
                                contentClassName="space-y-3"
                            >
                                {athlete.training?.upcomingSessions.length ? (
                                    athlete.training.upcomingSessions.map((session) => (
                                        <div
                                            key={`${session.title}-${session.scheduledDate ?? 'date'}`}
                                            className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4"
                                        >
                                            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                                <p className="font-medium text-stone-950">{session.title}</p>
                                                <Badge variant="outline">{session.exerciseCount} exercises</Badge>
                                            </div>
                                            <p className="mt-2 text-sm text-stone-600">
                                                {session.scheduledDate ? shortDayLabel(session.scheduledDate) : 'No date'}
                                                {session.focus ? ` · ${session.focus}` : ''}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm leading-6 text-stone-500">No sessions are queued after the next one yet.</p>
                                )}
                            </AthletePanel>

                            <AthletePanel
                                title="Recent logs"
                                description="Your last few logged sessions. This is the compliance trail."
                                contentClassName="space-y-3"
                            >
                                {athlete.training?.recentLogs.length ? (
                                    athlete.training.recentLogs.map((log) => (
                                        <div
                                            key={`${log.sessionTitle}-${log.performedAt ?? 'pending'}`}
                                            className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4"
                                        >
                                            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                                <p className="font-medium text-stone-950">{log.sessionTitle}</p>
                                                <Badge variant={badgeVariantForStatus(log.completionStatus)}>
                                                    {humanizeStatus(log.completionStatus)}
                                                </Badge>
                                            </div>
                                            <p className="mt-2 text-sm text-stone-600">
                                                {[
                                                    log.performedAt ? shortDayLabel(log.performedAt) : null,
                                                    log.durationMinutes ? `${log.durationMinutes} min` : null,
                                                    log.exertionRating ? `RPE ${log.exertionRating}` : null,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') || 'No detail logged'}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm leading-6 text-stone-500">
                                        No workout logs yet. The block only matters if you log the work.
                                    </p>
                                )}
                            </AthletePanel>
                        </div>
                    </div>
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Support"
                        title="Membership clarity and coach visibility."
                        description="Athletes should always know who they are working with and how long platform access lasts."
                    />
                    <div className="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
                        <AthletePanel
                            title="Membership status"
                            description="The athlete should never ask support how many days are left."
                            contentClassName="space-y-4"
                        >
                            <div className="flex flex-wrap gap-2">
                                <Badge variant={badgeVariantForStatus(athlete.membership.status)}>{humanizeStatus(athlete.membership.status)}</Badge>
                                <Badge variant="outline">{athlete.membership.planName}</Badge>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Time left</p>
                                    <p className="mt-2 text-sm font-medium text-stone-950">{formatDays(athlete.membership.daysRemaining)}</p>
                                </div>
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Renews on</p>
                                    <p className="mt-2 text-sm font-medium text-stone-950">{athlete.membership.renewsAt ?? 'Not scheduled'}</p>
                                </div>
                                <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Auto renew</p>
                                    <p className="mt-2 text-sm font-medium text-stone-950">{athlete.membership.autoRenew ? 'Enabled' : 'Disabled'}</p>
                                </div>
                            </div>
                        </AthletePanel>

                        <AthletePanel
                            title="Coach loop"
                            description="Who you are assigned to and how the relationship is framed."
                            contentClassName="grid gap-3 md:grid-cols-2"
                        >
                            {athlete.coaches.length === 0 ? (
                                <p className="text-sm leading-6 text-stone-500 md:col-span-2">No coach has been assigned yet.</p>
                            ) : (
                                athlete.coaches.map((coachEntry) => (
                                    <div key={coachEntry.id} className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p className="font-medium text-stone-950">{coachEntry.name}</p>
                                                <p className="text-sm text-stone-500">{coachEntry.email}</p>
                                            </div>
                                            <Badge variant="outline">{humanizeStatus(coachEntry.relationshipStatus)}</Badge>
                                        </div>
                                        <p className="mt-3 text-sm leading-6 text-stone-600">{coachEntry.goal ?? 'General coaching relationship'}</p>
                                    </div>
                                ))
                            )}
                        </AthletePanel>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

export default function Dashboard({ viewer, admin, coach, athlete }: DashboardPageProps) {
    if (athlete && !admin && !coach) {
        return <AthleteDashboardExperience viewer={viewer} athlete={athlete} />;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Card className="border-sidebar-border/70 from-background via-background to-muted/40 bg-linear-to-br">
                    <CardHeader>
                        <div className="flex flex-wrap items-center gap-2">
                            {viewer.roles.map((role) => (
                                <Badge key={role.name} variant="outline">
                                    {role.label}
                                </Badge>
                            ))}
                        </div>
                        <CardTitle className="text-3xl">Welcome back, {viewer.name}.</CardTitle>
                        <CardDescription className="max-w-3xl leading-6">
                            This dashboard now ties onboarding, memberships, training, and device health together. That is the product, not the
                            marketing sentence.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div className="border-sidebar-border/70 bg-background/80 rounded-xl border p-4">
                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Primary goal</p>
                            <p className="mt-2 text-sm font-medium">{viewer.primaryGoal ?? 'Not set yet'}</p>
                        </div>
                        <div className="border-sidebar-border/70 bg-background/80 rounded-xl border p-4">
                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Preferred contact</p>
                            <p className="mt-2 text-sm font-medium">{humanizeStatus(viewer.preferredContactMethod ?? 'email')}</p>
                        </div>
                        <div className="border-sidebar-border/70 bg-background/80 rounded-xl border p-4">
                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Signup channel</p>
                            <p className="mt-2 text-sm font-medium">{humanizeStatus(viewer.registrationChannel ?? 'email')}</p>
                        </div>
                        <div className="border-sidebar-border/70 bg-background/80 rounded-xl border p-4">
                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Contact details</p>
                            <p className="mt-2 text-sm font-medium">{viewer.phone ?? viewer.email}</p>
                        </div>
                    </CardContent>
                </Card>

                <section className={cn('grid gap-4', admin ? 'md:grid-cols-2 xl:grid-cols-4' : 'md:grid-cols-3')}>
                    {(admin || coach) && (
                        <QuickLinkCard title="Roster" href="/roster" note="Coach-athlete assignments and roster control." icon={Users} />
                    )}
                    <QuickLinkCard title="Training" href="/training" note="Programs, sessions, and workout logs." icon={Dumbbell} />
                    <QuickLinkCard title="Progress" href="/progress" note="Food, weight, hydration, and manual athlete check-ins." icon={LineChart} />
                    <QuickLinkCard
                        title="Memberships"
                        href="/memberships"
                        note="Billing state, renewals, and membership control."
                        icon={CreditCard}
                    />
                    <QuickLinkCard title="Wearables" href="/wearables" note="Device health, ingest, and recovery signals." icon={Watch} />
                    {admin && (
                        <QuickLinkCard
                            title="Control center"
                            href="/admin/control-center"
                            note="Renewals, payment failures, and ops triage."
                            icon={Shield}
                        />
                    )}
                    {admin && <QuickLinkCard title="Users" href="/admin/users" note="Roles, onboarding fields, and account control." icon={Users} />}
                </section>

                {admin && (
                    <section className="space-y-4">
                        <div className="space-y-1">
                            <h2 className="text-xl font-semibold tracking-tight">Admin overview</h2>
                            <p className="text-muted-foreground text-sm leading-6">
                                Platform health, money, training activity, and where operations will hurt next.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <MetricCard
                                title="Platform users"
                                value={admin.metrics.totalUsers.toString()}
                                note={`${admin.metrics.totalCoaches} coaches and ${admin.metrics.totalAthletes} athletes are on the platform.`}
                                icon={Users}
                            />
                            <MetricCard
                                title="Active memberships"
                                value={admin.metrics.activeMemberships.toString()}
                                note={`${admin.metrics.expiringMemberships} memberships need renewal attention this week.`}
                                icon={CreditCard}
                            />
                            <MetricCard
                                title="Connected devices"
                                value={admin.metrics.connectedDevices.toString()}
                                note={`${admin.metrics.attentionConnections} connections are currently in trouble.`}
                                icon={Watch}
                            />
                            <MetricCard
                                title="Projected monthly revenue"
                                value={formatCurrency(admin.metrics.projectedMonthlyRevenue)}
                                note={`Estimated payout liability: ${formatCurrency(admin.metrics.coachPayoutLiability)}.`}
                                icon={Shield}
                            />
                            <MetricCard
                                title="Active programs"
                                value={admin.metrics.activePrograms.toString()}
                                note={`${admin.metrics.scheduledSessionsThisWeek} sessions are scheduled over the next seven days.`}
                                icon={Dumbbell}
                            />
                            <MetricCard
                                title="Workout logs this week"
                                value={admin.metrics.loggedSessionsThisWeek.toString()}
                                note="This is real athlete execution data, not fantasy compliance."
                                icon={Activity}
                            />
                            <MetricCard
                                title="Collected this month"
                                value={formatCurrency(admin.metrics.paymentVolumeThisMonth)}
                                note={`${admin.metrics.failedPaymentsThisMonth} payment events failed this month.`}
                                icon={CreditCard}
                            />
                            <MetricCard
                                title="New users this month"
                                value={admin.metrics.newUsersThisMonth.toString()}
                                note="Fresh accounts created since the month started."
                                icon={Users}
                            />
                        </div>

                        <div className="grid gap-4 xl:grid-cols-3">
                            <Card className="border-sidebar-border/70 xl:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Renewal watchlist</CardTitle>
                                    <CardDescription>Users approaching renewal or grace boundaries.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {admin.expiringMembershipWatchlist.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">No memberships need review right now.</p>
                                    ) : (
                                        admin.expiringMembershipWatchlist.map((entry) => (
                                            <div
                                                key={`${entry.userName}-${entry.planName}`}
                                                className="border-sidebar-border/70 flex flex-col gap-3 rounded-xl border p-4 md:flex-row md:items-center md:justify-between"
                                            >
                                                <div>
                                                    <p className="font-medium">{entry.userName}</p>
                                                    <p className="text-muted-foreground text-sm">{entry.planName}</p>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                    <span className="text-muted-foreground text-sm">{formatDays(entry.daysRemaining)}</span>
                                                    <span className="text-muted-foreground text-sm">{entry.endsAt ?? 'No end date'}</span>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70">
                                <CardHeader>
                                    <CardTitle className="text-lg">Signup method mix</CardTitle>
                                    <CardDescription>Current usage now, future auth rollout later.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {admin.signupMix.map((entry) => (
                                        <div key={entry.method} className="border-sidebar-border/70 rounded-xl border p-4">
                                            <div className="flex items-center justify-between gap-3">
                                                <p className="font-medium">{entry.label}</p>
                                                <Badge variant={entry.enabled ? 'default' : 'secondary'}>{entry.enabled ? 'Live' : 'Staged'}</Badge>
                                            </div>
                                            <p className="text-muted-foreground mt-2 text-sm">{entry.count} account(s)</p>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-4 xl:grid-cols-2">
                            <Card className="border-sidebar-border/70">
                                <CardHeader>
                                    <CardTitle className="text-lg">Device attention queue</CardTitle>
                                    <CardDescription>Connections that need somebody to stop ignoring them.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {admin.deviceAttentionQueue.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">No device issues are queued right now.</p>
                                    ) : (
                                        admin.deviceAttentionQueue.map((entry) => (
                                            <div
                                                key={`${entry.userName}-${entry.provider}`}
                                                className="border-sidebar-border/70 flex flex-col gap-3 rounded-xl border p-4 md:flex-row md:items-center md:justify-between"
                                            >
                                                <div>
                                                    <p className="font-medium">{entry.userName}</p>
                                                    <p className="text-muted-foreground text-sm">{entry.provider}</p>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                    <span className="text-muted-foreground text-sm">{entry.lastSyncedAt ?? 'Never synced'}</span>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70">
                                <CardHeader>
                                    <CardTitle className="text-lg">Payment attention queue</CardTitle>
                                    <CardDescription>Pending and failed money events that still need a human brain.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {admin.paymentAttentionQueue.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">No payment issues are queued right now.</p>
                                    ) : (
                                        admin.paymentAttentionQueue.map((entry) => (
                                            <div
                                                key={`${entry.userName}-${entry.reference ?? entry.eventAt ?? entry.planName}`}
                                                className="border-sidebar-border/70 rounded-xl border p-4"
                                            >
                                                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                                    <div>
                                                        <p className="font-medium">{entry.userName}</p>
                                                        <p className="text-muted-foreground text-sm">{entry.planName}</p>
                                                    </div>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                                        <span className="text-muted-foreground text-sm">
                                                            {entry.amount === null
                                                                ? 'No amount'
                                                                : new Intl.NumberFormat('en-US', {
                                                                      style: 'currency',
                                                                      currency: entry.currency,
                                                                      maximumFractionDigits: 0,
                                                                  }).format(entry.amount)}
                                                        </span>
                                                    </div>
                                                </div>
                                                <p className="text-muted-foreground mt-3 text-sm">
                                                    {entry.reference ? `${entry.reference} · ` : ''}
                                                    {entry.eventAt ?? 'No timestamp'}
                                                </p>
                                                <p className="mt-2 text-sm leading-6">{entry.notes ?? 'No notes attached.'}</p>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </section>
                )}

                {coach && (
                    <section className="space-y-4">
                        <div className="space-y-1">
                            <h2 className="text-xl font-semibold tracking-tight">Coach workspace</h2>
                            <p className="text-muted-foreground text-sm leading-6">
                                Roster risk, training activity, and the next sessions that will either go smoothly or become excuses.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                title="Roster size"
                                value={coach.metrics.rosterCount.toString()}
                                note={`${coach.metrics.athletesNeedingAttention} athletes currently need direct attention.`}
                                icon={Users}
                            />
                            <MetricCard
                                title="Active programs"
                                value={coach.metrics.activePrograms.toString()}
                                note="Programs currently carrying real work, not archived leftovers."
                                icon={Dumbbell}
                            />
                            <MetricCard
                                title="Upcoming sessions"
                                value={coach.metrics.upcomingSessions.toString()}
                                note="Seven-day view of scheduled coaching workload."
                                icon={Activity}
                            />
                            <MetricCard
                                title="Pending workout logs"
                                value={coach.metrics.pendingWorkoutLogs.toString()}
                                note="Past-due athlete feedback the coach still needs."
                                icon={CreditCard}
                            />
                        </div>

                        <div className="grid gap-4 xl:grid-cols-3">
                            <Card className="border-sidebar-border/70 xl:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Roster</CardTitle>
                                    <CardDescription>Current athlete assignments with membership, recovery, and program context.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {coach.roster.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">No athletes assigned yet.</p>
                                    ) : (
                                        coach.roster.map((athleteEntry) => (
                                            <div
                                                key={athleteEntry.id}
                                                className="border-sidebar-border/70 hover:bg-muted/30 rounded-xl border p-4 transition-colors"
                                            >
                                                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                    <div className="space-y-1">
                                                        <p className="font-medium">{athleteEntry.name}</p>
                                                        <p className="text-muted-foreground text-sm">{athleteEntry.email}</p>
                                                        <p className="text-muted-foreground text-sm">Current focus: {athleteEntry.goal}</p>
                                                        {athleteEntry.currentProgram ? (
                                                            <p className="text-muted-foreground text-sm">
                                                                Program: {athleteEntry.currentProgram.title} · Next session{' '}
                                                                {athleteEntry.currentProgram.nextSessionDate ?? 'not scheduled'}.
                                                            </p>
                                                        ) : (
                                                            <p className="text-muted-foreground text-sm">No current training program is assigned.</p>
                                                        )}
                                                        {athleteEntry.latestSnapshot && (
                                                            <p className="text-muted-foreground text-sm">
                                                                Recovery: {formatReadiness(athleteEntry.latestSnapshot.readinessScore)},{' '}
                                                                {formatSleepHours(athleteEntry.latestSnapshot.sleepHours)}, strain{' '}
                                                                {athleteEntry.latestSnapshot.strainScore ?? 'N/A'}.
                                                            </p>
                                                        )}
                                                        {athleteEntry.latestCheckIn && (
                                                            <p className="text-muted-foreground text-sm">
                                                                Progress: {formatWeight(athleteEntry.latestCheckIn.weightKg)} ·{' '}
                                                                {formatCalories(athleteEntry.latestCheckIn.caloriesConsumed)} ·{' '}
                                                                {formatGrams(athleteEntry.latestCheckIn.proteinGrams)} · Water{' '}
                                                                {formatLiters(athleteEntry.latestCheckIn.waterLiters)}.
                                                            </p>
                                                        )}
                                                    </div>

                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge variant={badgeVariantForStatus(athleteEntry.membershipStatus)}>
                                                            {humanizeStatus(athleteEntry.membershipStatus)}
                                                        </Badge>
                                                        <Badge variant="outline">{athleteEntry.membershipPlan}</Badge>
                                                        <Badge variant="outline">{athleteEntry.connectedDevices} connected devices</Badge>
                                                    </div>
                                                </div>

                                                <div className="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                                    <p className="text-muted-foreground text-sm">{formatDays(athleteEntry.daysRemaining)}</p>
                                                    <div className="flex flex-wrap gap-2">
                                                        {athleteEntry.flags.length === 0 ? (
                                                            <Badge variant="default">Stable</Badge>
                                                        ) : (
                                                            athleteEntry.flags.map((flag) => (
                                                                <Badge key={flag} variant="secondary">
                                                                    {flag}
                                                                </Badge>
                                                            ))
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>

                            <div className="space-y-4">
                                <Card className="border-sidebar-border/70">
                                    <CardHeader>
                                        <CardTitle className="text-lg">Attention queue</CardTitle>
                                        <CardDescription>The roster entries most likely to become coach headaches next.</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {coach.attentionQueue.length === 0 ? (
                                            <p className="text-muted-foreground text-sm">Nobody is actively waving a red flag right now.</p>
                                        ) : (
                                            coach.attentionQueue.map((entry) => (
                                                <div key={entry.id} className="border-sidebar-border/70 rounded-xl border p-4">
                                                    <p className="font-medium">{entry.name}</p>
                                                    <p className="text-muted-foreground mt-1 text-sm">{entry.goal}</p>
                                                    {entry.currentProgram && (
                                                        <p className="text-muted-foreground mt-1 text-sm">
                                                            {entry.currentProgram.title} · {entry.currentProgram.pendingLogs} pending log(s)
                                                        </p>
                                                    )}
                                                    <div className="mt-3 flex flex-wrap gap-2">
                                                        {entry.flags.map((flag) => (
                                                            <Badge key={flag} variant="secondary">
                                                                {flag}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </CardContent>
                                </Card>

                                <Card className="border-sidebar-border/70">
                                    <CardHeader>
                                        <CardTitle className="text-lg">Upcoming sessions</CardTitle>
                                        <CardDescription>What the next seven days are about to demand.</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {coach.upcomingSessions.length === 0 ? (
                                            <p className="text-muted-foreground text-sm">Nothing is scheduled in the next week.</p>
                                        ) : (
                                            coach.upcomingSessions.map((session) => (
                                                <div
                                                    key={`${session.athleteName}-${session.sessionTitle}`}
                                                    className="border-sidebar-border/70 rounded-xl border p-4"
                                                >
                                                    <p className="font-medium">{session.sessionTitle}</p>
                                                    <p className="text-muted-foreground mt-1 text-sm">
                                                        {session.athleteName} · {session.programTitle}
                                                    </p>
                                                    <p className="text-muted-foreground mt-1 text-sm">
                                                        {session.scheduledDate ?? 'No date'}
                                                        {session.focus ? ` · ${session.focus}` : ''}
                                                    </p>
                                                </div>
                                            ))
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </section>
                )}

                {athlete && (
                    <section className="space-y-4">
                        <div className="space-y-1">
                            <h2 className="text-xl font-semibold tracking-tight">Athlete view</h2>
                            <p className="text-muted-foreground text-sm leading-6">
                                Training clarity, billing visibility, and device health without turning the dashboard into a spreadsheet.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                title="Days remaining"
                                value={athlete.metrics.membershipDaysRemaining === null ? 'N/A' : athlete.metrics.membershipDaysRemaining.toString()}
                                note={`Current access is ${humanizeStatus(athlete.membership.status)}.`}
                                icon={CreditCard}
                            />
                            <MetricCard
                                title="Connected devices"
                                value={athlete.metrics.connectedDevices.toString()}
                                note="Wearables only matter when the data actually lands."
                                icon={Watch}
                            />
                            <MetricCard
                                title="Upcoming sessions"
                                value={athlete.metrics.upcomingSessionsCount.toString()}
                                note="Seven-day count across the current assigned training plan."
                                icon={Dumbbell}
                            />
                            <MetricCard
                                title="Completed this week"
                                value={athlete.metrics.completedSessionsThisWeek.toString()}
                                note={`Latest readiness: ${formatReadiness(athlete.metrics.latestReadinessScore)}.`}
                                icon={Activity}
                            />
                        </div>

                        <div className="grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
                            <Card className="border-sidebar-border/70">
                                <CardHeader>
                                    <CardTitle className="text-lg">Membership status</CardTitle>
                                    <CardDescription>The athlete should never need support to answer “how long do I have left?”</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant={badgeVariantForStatus(athlete.membership.status)}>
                                            {humanizeStatus(athlete.membership.status)}
                                        </Badge>
                                        <Badge variant="outline">{athlete.membership.planName}</Badge>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-sm">Time left</p>
                                            <p className="mt-2 text-sm font-medium">{formatDays(athlete.membership.daysRemaining)}</p>
                                        </div>
                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-sm">Renews on</p>
                                            <p className="mt-2 text-sm font-medium">{athlete.membership.renewsAt ?? 'Not scheduled'}</p>
                                        </div>
                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-sm">Auto renew</p>
                                            <p className="mt-2 text-sm font-medium">{athlete.membership.autoRenew ? 'Enabled' : 'Disabled'}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70">
                                <CardHeader>
                                    <CardTitle className="text-lg">Assigned coaches</CardTitle>
                                    <CardDescription>{athlete.metrics.coachCount} active coaching relationship(s).</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {athlete.coaches.length === 0 ? (
                                        <p className="text-muted-foreground text-sm">No coach has been assigned yet.</p>
                                    ) : (
                                        athlete.coaches.map((coachEntry) => (
                                            <div key={coachEntry.id} className="border-sidebar-border/70 rounded-xl border p-4">
                                                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                                    <div>
                                                        <p className="font-medium">{coachEntry.name}</p>
                                                        <p className="text-muted-foreground text-sm">{coachEntry.email}</p>
                                                    </div>
                                                    <Badge variant="outline">{humanizeStatus(coachEntry.relationshipStatus)}</Badge>
                                                </div>
                                                <p className="text-muted-foreground mt-3 text-sm">
                                                    Focus: {coachEntry.goal ?? 'General coaching relationship'}
                                                </p>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-4 xl:grid-cols-3">
                            <Card className="border-sidebar-border/70 xl:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Current training block</CardTitle>
                                    <CardDescription>
                                        {athlete.training
                                            ? 'The dashboard version of your plan. Full detail still lives on the training page.'
                                            : 'No current training block is assigned yet.'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {athlete.training ? (
                                        <>
                                            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                <div className="space-y-1">
                                                    <p className="text-xl font-semibold tracking-tight">{athlete.training.title}</p>
                                                    <p className="text-muted-foreground text-sm">Coach: {athlete.training.coachName}</p>
                                                    <p className="text-muted-foreground text-sm">
                                                        {athlete.training.goal ?? 'No goal has been written down yet.'}
                                                    </p>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge variant={badgeVariantForStatus(athlete.training.status)}>
                                                        {humanizeStatus(athlete.training.status)}
                                                    </Badge>
                                                    <Badge variant="outline">
                                                        {athlete.training.startDate ?? 'No start'} to {athlete.training.endDate ?? 'Open end'}
                                                    </Badge>
                                                </div>
                                            </div>

                                            <div className="grid gap-4 lg:grid-cols-2">
                                                <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                    <p className="text-sm font-medium">Next session</p>
                                                    {athlete.training.nextSession ? (
                                                        <div className="mt-3 space-y-3">
                                                            <div className="text-muted-foreground space-y-1 text-sm">
                                                                <p className="text-foreground font-medium">{athlete.training.nextSession.title}</p>
                                                                <p>
                                                                    {athlete.training.nextSession.scheduledDate ?? 'No date'}
                                                                    {athlete.training.nextSession.focus
                                                                        ? ` · ${athlete.training.nextSession.focus}`
                                                                        : ''}
                                                                </p>
                                                                {athlete.training.nextSession.instructions && (
                                                                    <p className="leading-6">{athlete.training.nextSession.instructions}</p>
                                                                )}
                                                            </div>
                                                            {athlete.training.nextSession.exercises.length > 0 && (
                                                                <div className="grid gap-3">
                                                                    {athlete.training.nextSession.exercises.map((exercise, index) => (
                                                                        <ExerciseChipCard
                                                                            key={`${athlete.training.nextSession?.title}-${index}`}
                                                                            exercise={exercise}
                                                                        />
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <p className="text-muted-foreground mt-3 text-sm">Nothing upcoming is scheduled yet.</p>
                                                    )}
                                                </div>

                                                <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                    <p className="text-sm font-medium">Recent logs</p>
                                                    <div className="mt-3 space-y-3">
                                                        {athlete.training.recentLogs.length === 0 ? (
                                                            <p className="text-muted-foreground text-sm">No workout logs have been submitted yet.</p>
                                                        ) : (
                                                            athlete.training.recentLogs.map((log) => (
                                                                <div
                                                                    key={`${log.sessionTitle}-${log.performedAt}`}
                                                                    className="text-muted-foreground text-sm"
                                                                >
                                                                    <p className="text-foreground font-medium">{log.sessionTitle}</p>
                                                                    <p>
                                                                        {humanizeStatus(log.completionStatus)} · {log.performedAt ?? 'No date'}
                                                                        {log.durationMinutes ? ` · ${log.durationMinutes} min` : ''}
                                                                        {log.exertionRating ? ` · RPE ${log.exertionRating}` : ''}
                                                                    </p>
                                                                </div>
                                                            ))
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                <p className="text-sm font-medium">Upcoming sessions</p>
                                                <div className="mt-3 grid gap-3 md:grid-cols-2">
                                                    {athlete.training.upcomingSessions.length === 0 ? (
                                                        <p className="text-muted-foreground text-sm">No upcoming sessions are scheduled yet.</p>
                                                    ) : (
                                                        athlete.training.upcomingSessions.map((session) => (
                                                            <div
                                                                key={`${session.title}-${session.scheduledDate}`}
                                                                className="border-sidebar-border/70 rounded-xl border p-3"
                                                            >
                                                                <p className="font-medium">{session.title}</p>
                                                                <p className="text-muted-foreground mt-1 text-sm">
                                                                    {session.scheduledDate ?? 'No date'}
                                                                    {session.focus ? ` · ${session.focus}` : ''}
                                                                </p>
                                                                <p className="text-muted-foreground mt-1 text-sm">
                                                                    {session.exerciseCount} exercise item(s)
                                                                </p>
                                                            </div>
                                                        ))
                                                    )}
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <p className="text-muted-foreground text-sm">
                                            Ask the coach to assign a program before expecting this section to do miracles.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <div className="space-y-4">
                                <Card className="border-sidebar-border/70">
                                    <CardHeader>
                                        <CardTitle className="text-lg">Latest recovery snapshot</CardTitle>
                                        <CardDescription>
                                            {athlete.latestSnapshot?.metricDate
                                                ? `Most recent normalized metrics from ${athlete.latestSnapshot.metricDate}.`
                                                : 'No normalized recovery snapshot has been ingested yet.'}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {athlete.latestSnapshot ? (
                                            <>
                                                <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                    <p className="text-muted-foreground text-sm">Readiness</p>
                                                    <p className="mt-2 text-2xl font-semibold">
                                                        {formatReadiness(athlete.latestSnapshot.readinessScore)}
                                                    </p>
                                                </div>
                                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Sleep</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatSleepHours(athlete.latestSnapshot.sleepHours)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Sleep need</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatSleepHours(athlete.latestSnapshot.sleepNeedHours)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Sleep debt</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatSleepDebt(athlete.latestSnapshot.sleepDebtHours)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">HRV</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {athlete.latestSnapshot.heartRateVariability ?? 'N/A'}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Resting HR</p>
                                                        <p className="mt-2 text-sm font-medium">{athlete.latestSnapshot.restingHeartRate ?? 'N/A'}</p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Respiration</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {athlete.latestSnapshot.respiratoryRate === null
                                                                ? 'N/A'
                                                                : `${athlete.latestSnapshot.respiratoryRate} rpm`}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Blood oxygen</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {athlete.latestSnapshot.bloodOxygenPercent === null
                                                                ? 'N/A'
                                                                : `${athlete.latestSnapshot.bloodOxygenPercent}%`}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Sleep performance</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatPercentage(athlete.latestSnapshot.sleepPerformancePercentage)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Sleep consistency</p>
                                                        <p className="mt-2 text-sm font-medium">
                                                            {formatPercentage(athlete.latestSnapshot.sleepConsistencyPercentage)}
                                                        </p>
                                                    </div>
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <p className="text-muted-foreground text-sm">Training load</p>
                                                        <p className="mt-2 text-sm font-medium">{athlete.latestSnapshot.trainingLoad ?? 'N/A'}</p>
                                                    </div>
                                                </div>
                                                {athlete.metricReport.overview.daysTracked > 0 && (
                                                    <div className="border-sidebar-border/70 rounded-xl border p-4">
                                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                                            <p className="text-sm font-medium">Recent trend</p>
                                                            <Badge variant="outline">
                                                                {athlete.metricReport.overview.daysTracked} tracked day(s)
                                                            </Badge>
                                                        </div>
                                                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                                            <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                                <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                    Average readiness
                                                                </p>
                                                                <p className="mt-2 text-sm font-medium">
                                                                    {formatReadiness(athlete.metricReport.overview.averageReadiness)}
                                                                </p>
                                                            </div>
                                                            <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                                <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                    Readiness delta
                                                                </p>
                                                                <p className="mt-2 text-sm font-medium">
                                                                    {formatSignedDelta(athlete.metricReport.overview.readinessDelta)}
                                                                </p>
                                                            </div>
                                                            <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                                <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                    Average sleep
                                                                </p>
                                                                <p className="mt-2 text-sm font-medium">
                                                                    {formatSleepHours(athlete.metricReport.overview.averageSleepHours)}
                                                                </p>
                                                            </div>
                                                            <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                                <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                    Average HRV
                                                                </p>
                                                                <p className="mt-2 text-sm font-medium">
                                                                    {athlete.metricReport.overview.averageHrv ?? 'N/A'}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        {athlete.metricReport.alerts.length > 0 && (
                                                            <div className="mt-3 flex flex-wrap gap-2">
                                                                {athlete.metricReport.alerts.map((alert) => (
                                                                    <Badge key={alert} variant="secondary">
                                                                        {alert}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        )}
                                                        <div className="mt-4 grid gap-2">
                                                            {athlete.metricReport.timeline.map((entry) => (
                                                                <div
                                                                    key={entry.metricDate ?? 'unknown-date'}
                                                                    className="border-sidebar-border/70 text-muted-foreground grid gap-2 rounded-xl border p-3 text-sm sm:grid-cols-4"
                                                                >
                                                                    <p className="text-foreground font-medium">
                                                                        {entry.metricDate ?? 'Unknown date'}
                                                                    </p>
                                                                    <p>Readiness {formatReadiness(entry.readinessScore)}</p>
                                                                    <p>Sleep {formatSleepHours(entry.sleepHours)}</p>
                                                                    <p>Strain {entry.strainScore ?? 'N/A'}</p>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </>
                                        ) : (
                                            <p className="text-muted-foreground text-sm">
                                                Connect a wearable and post normalized daily metrics to make this useful.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card className="border-sidebar-border/70">
                                    <CardHeader>
                                        <CardTitle className="text-lg">Device connections</CardTitle>
                                        <CardDescription>Recovery signal coverage at a glance.</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {athlete.deviceConnections.length === 0 ? (
                                            <p className="text-muted-foreground text-sm">No device is connected yet.</p>
                                        ) : (
                                            athlete.deviceConnections.map((connection) => (
                                                <div
                                                    key={`${connection.provider}-${connection.lastSyncedAt}`}
                                                    className={cn('border-sidebar-border/70 rounded-xl border p-4')}
                                                >
                                                    <div className="flex items-center justify-between gap-3">
                                                        <div>
                                                            <p className="font-medium">{connection.providerLabel}</p>
                                                            <p className="text-muted-foreground text-sm">
                                                                {connection.lastSyncedAt ?? 'Never synced'}
                                                            </p>
                                                        </div>
                                                        <Badge variant={badgeVariantForStatus(connection.status)}>
                                                            {humanizeStatus(connection.status)}
                                                        </Badge>
                                                    </div>
                                                    {connection.latestSnapshot && (
                                                        <p className="text-muted-foreground mt-3 text-sm">
                                                            {formatReadiness(connection.latestSnapshot.readinessScore)} and{' '}
                                                            {formatSleepHours(connection.latestSnapshot.sleepHours)}.
                                                        </p>
                                                    )}
                                                </div>
                                            ))
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
