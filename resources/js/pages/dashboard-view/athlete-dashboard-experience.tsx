import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading, ReadinessDial, TrendBars } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { WorkspaceTable, WorkspaceTableEmpty, WorkspaceTableHeader } from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import {
    badgeVariantForStatus,
    formatCalories,
    formatDays,
    formatGrams,
    formatLiters,
    formatPercentage,
    formatReadiness,
    formatRestLabel,
    formatSignedDelta,
    formatSleepDebt,
    formatSleepHours,
    formatWeight,
    humanizeStatus,
    shortDayLabel,
} from '@/pages/dashboard-view/helpers';
import { dashboardBreadcrumbs, type AthleteOverview, type Viewer } from '@/pages/dashboard-view/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Beef,
    CalendarDays,
    ClipboardPenLine,
    Clock3,
    CreditCard,
    Droplets,
    Flame,
    HeartPulse,
    MessageCircle,
    MoonStar,
    Newspaper,
    PlayCircle,
    Scale,
    Trophy,
    TrendingUp,
    User,
    Watch,
} from 'lucide-react';

function AthleteMobileAppPreview({ viewer, athlete }: { viewer: Viewer; athlete: AthleteOverview }) {
    const nextSession = athlete.training?.nextSession;
    const latestSnapshot = athlete.latestSnapshot;
    const wearableConnected = athlete.metrics.connectedDevices > 0;
    const previewExercises = nextSession?.exercises.slice(0, 4) ?? [];
    const firstExercise = previewExercises[0] ?? null;

    return (
        <section className="grid gap-5 xl:grid-cols-[0.82fr_1.18fr]">
            <div className="mx-auto w-full max-w-[430px] rounded-[2.2rem] border border-stone-200 bg-white p-3 shadow-[0_30px_90px_-60px_rgba(15,23,42,0.75)]">
                <div className="overflow-hidden rounded-[1.8rem] border border-emerald-900/10 bg-[#f8faf7]">
                    <div className="bg-[linear-gradient(135deg,#0f6b4f,#2f8a64)] px-5 pt-5 pb-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.22em] text-emerald-100 uppercase">Dashboard</p>
                                <h2 className="mt-2 text-2xl font-bold tracking-tight">SOTC</h2>
                            </div>
                            <Badge className="border-white/30 bg-white/15 text-white hover:bg-white/20">Athlete</Badge>
                        </div>
                        <p className="mt-5 text-lg font-semibold">Welcome, {viewer.name.split(' ')[0]}</p>
                        <p className="mt-1 text-sm text-emerald-50">
                            {athlete.training?.coachName ? `Coach: ${athlete.training.coachName}` : 'Coach assignment pending'}
                        </p>
                    </div>

                    <div className="-mt-4 space-y-4 px-4 pb-4">
                        <div className="rounded-[1.35rem] border border-emerald-100 bg-white p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Exertion score</p>
                                    <p className="mt-2 text-4xl font-black tracking-tight text-stone-950">
                                        {athlete.metrics.latestReadinessScore === null ? '--' : Math.round(athlete.metrics.latestReadinessScore)}
                                    </p>
                                    <p className="mt-1 text-sm text-stone-500">
                                        {wearableConnected
                                            ? `Synced ${latestSnapshot?.metricDate ? shortDayLabel(latestSnapshot.metricDate) : 'recently'}`
                                            : 'Connect your wearable to unlock recovery scoring.'}
                                    </p>
                                </div>
                                <div className="grid size-14 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                    <Watch className="size-6" />
                                </div>
                            </div>
                            {!wearableConnected && (
                                <Button asChild size="sm" className="mt-4 w-full rounded-full bg-emerald-700 text-white hover:bg-emerald-800">
                                    <Link href="/wearables">Connect your wearable</Link>
                                </Button>
                            )}
                        </div>

                        <div className="rounded-[1.35rem] border border-stone-200 bg-white p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Today workout</p>
                                    <h3 className="mt-2 text-xl font-black tracking-tight text-stone-950">
                                        {nextSession?.title ?? 'Loading workout'}
                                    </h3>
                                    <p className="mt-1 text-sm text-stone-500">
                                        {nextSession?.focus ?? athlete.training?.goal ?? 'Coach will assign the next session.'}
                                    </p>
                                </div>
                                <Badge variant="outline">{nextSession?.scheduledDate ? shortDayLabel(nextSession.scheduledDate) : 'Today'}</Badge>
                            </div>

                            <div className="mt-4 grid gap-2">
                                {previewExercises.length === 0 ? (
                                    <div className="rounded-xl border border-dashed border-stone-200 p-3 text-sm text-stone-500">
                                        No exercise rows are attached yet.
                                    </div>
                                ) : (
                                    previewExercises.map((exercise) => (
                                        <div key={`${nextSession?.title}-${exercise.name}`} className="rounded-xl bg-stone-50 px-3 py-2">
                                            <div className="flex items-center justify-between gap-3">
                                                <p className="font-semibold text-stone-950">{exercise.name}</p>
                                                <span className="text-sm font-medium text-emerald-700">
                                                    {exercise.sets ?? '--'} x {exercise.reps ?? exercise.prescription ?? '--'}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-xs text-stone-500">
                                                {exercise.load ?? 'Load open'} · {formatRestLabel(exercise.rest_seconds, exercise.rest_label) ?? 'Rest open'}
                                            </p>
                                        </div>
                                    ))
                                )}
                            </div>

                            <div className="mt-4 grid grid-cols-3 gap-2 text-center text-xs font-bold tracking-[0.14em] uppercase">
                                <span className="rounded-full bg-stone-950 px-3 py-2 text-white">Journal</span>
                                <span className="rounded-full bg-stone-100 px-3 py-2 text-stone-700">Media</span>
                                <span className="rounded-full bg-stone-100 px-3 py-2 text-stone-700">Opt out</span>
                            </div>
                        </div>

                        <div className="rounded-[1.35rem] border border-stone-200 bg-white p-4 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Current exercise</p>
                                    <p className="mt-1 font-black text-stone-950">{firstExercise?.name ?? 'No exercise selected'}</p>
                                </div>
                                <PlayCircle className="size-8 text-emerald-700" />
                            </div>
                            <div className="mt-4 grid grid-cols-3 gap-2">
                                <div className="rounded-xl bg-stone-50 p-3">
                                    <p className="text-xs text-stone-500">Sets</p>
                                    <p className="text-lg font-black">{firstExercise?.sets ?? '--'}</p>
                                </div>
                                <div className="rounded-xl bg-stone-50 p-3">
                                    <p className="text-xs text-stone-500">Reps</p>
                                    <p className="text-lg font-black">{firstExercise?.reps ?? '--'}</p>
                                </div>
                                <div className="rounded-xl bg-stone-50 p-3">
                                    <p className="text-xs text-stone-500">Load</p>
                                    <p className="text-lg font-black">{firstExercise?.load ?? '--'}</p>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-5 gap-1 rounded-[1.2rem] border border-stone-200 bg-white px-2 py-2 text-center text-[0.68rem] font-semibold text-stone-500">
                            {[
                                ['Feed', Newspaper],
                                ['Board', Trophy],
                                ['Workout', CalendarDays],
                                ['Chat', MessageCircle],
                                ['Profile', User],
                            ].map(([label, Icon]) => (
                                <div key={label as string} className={label === 'Workout' ? 'text-emerald-700' : undefined}>
                                    <Icon className="mx-auto mb-1 size-4" />
                                    {label as string}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            <AthletePanel
                title="Phone-first workout control"
                description="This is the app direction from the reference video: today first, wearable prompt second, then set logging, journal/media, and coach context."
                contentClassName="space-y-4"
            >
                <div className="grid gap-3 md:grid-cols-3">
                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                        <p className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Workout state</p>
                        <p className="mt-2 text-lg font-black text-stone-950">{nextSession ? 'Assigned' : 'Not assigned'}</p>
                        <p className="mt-1 text-sm text-stone-500">{nextSession?.title ?? 'Coach must schedule the next workout.'}</p>
                    </div>
                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                        <p className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Wearable</p>
                        <p className="mt-2 text-lg font-black text-stone-950">{wearableConnected ? 'Connected' : 'Missing'}</p>
                        <p className="mt-1 text-sm text-stone-500">{athlete.metrics.connectedDevices} connected device(s).</p>
                    </div>
                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                        <p className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Logging</p>
                        <p className="mt-2 text-lg font-black text-stone-950">{athlete.metrics.completedSessionsThisWeek}</p>
                        <p className="mt-1 text-sm text-stone-500">Completed session(s) this week.</p>
                    </div>
                </div>

                <WorkspaceTable minWidth="min-w-[860px]">
                    <WorkspaceTableHeader labels={['Exercise', 'Sets', 'Reps / time', 'Load', 'Rest', 'Target', 'Media']} />
                    {previewExercises.length === 0 ? (
                        <WorkspaceTableEmpty message="No exercise rows are available for the mobile workout view yet." colSpan={7} />
                    ) : (
                        <tbody className="divide-y divide-stone-200">
                            {previewExercises.map((exercise) => (
                                <tr key={`mobile-preview-${exercise.name}`} className="align-top">
                                    <td className="px-4 py-4">
                                        <p className="font-medium text-stone-950">{exercise.name}</p>
                                        <p className="mt-1 text-xs text-stone-500">{exercise.note ?? 'No coach note.'}</p>
                                    </td>
                                    <td className="px-4 py-4 text-stone-600">{exercise.sets ?? 'N/A'}</td>
                                    <td className="px-4 py-4 text-stone-600">{exercise.reps ?? exercise.prescription ?? 'N/A'}</td>
                                    <td className="px-4 py-4 text-stone-600">{exercise.load ?? 'N/A'}</td>
                                    <td className="px-4 py-4 text-stone-600">
                                        {formatRestLabel(exercise.rest_seconds, exercise.rest_label) ?? 'N/A'}
                                    </td>
                                    <td className="px-4 py-4 text-stone-600">{exercise.target ?? 'N/A'}</td>
                                    <td className="px-4 py-4">
                                        <Badge variant={nextSession?.videoUrl ? 'default' : 'outline'}>
                                            {nextSession?.videoUrl ? 'Video ready' : 'No video'}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    )}
                </WorkspaceTable>

                <div className="grid gap-3 md:grid-cols-3">
                    <Button asChild className="rounded-full bg-emerald-700 text-white hover:bg-emerald-800">
                        <Link href="/training">
                            <ClipboardPenLine className="size-4" />
                            Open workout log
                        </Link>
                    </Button>
                    <Button asChild variant="outline" className="rounded-full border-stone-300 bg-white">
                        <Link href="/wearables">Manage wearable</Link>
                    </Button>
                    <Button asChild variant="outline" className="rounded-full border-stone-300 bg-white">
                        <Link href="/progress">Journal progress</Link>
                    </Button>
                </div>
            </AthletePanel>
        </section>
    );
}

export function AthleteDashboardExperience({ viewer, athlete }: { viewer: Viewer; athlete: AthleteOverview }) {
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
        <AppLayout breadcrumbs={dashboardBreadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
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

                <AthleteMobileAppPreview viewer={viewer} athlete={athlete} />

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
                            contentClassName="p-0"
                        >
                            <WorkspaceTable minWidth="min-w-[720px]">
                                <WorkspaceTableHeader labels={['Provider', 'Status', 'Latest day', 'Readiness', 'Sleep', 'Strain', 'Last sync']} />
                                {athlete.deviceConnections.length === 0 ? (
                                    <WorkspaceTableEmpty message="No device connected yet. Recovery tracking needs a wearable connection." colSpan={7} />
                                ) : (
                                    <tbody className="divide-y divide-stone-200">
                                        {athlete.deviceConnections.map((connection) => (
                                            <tr key={`${connection.provider}-${connection.lastSyncedAt ?? 'never'}`} className="align-top">
                                                <td className="px-4 py-4 font-medium text-stone-950">{connection.providerLabel}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={badgeVariantForStatus(connection.status)}>{humanizeStatus(connection.status)}</Badge>
                                                </td>
                                                <td className="px-4 py-4 text-stone-600">
                                                    {connection.latestSnapshot?.metricDate
                                                        ? shortDayLabel(connection.latestSnapshot.metricDate)
                                                        : 'No metric day'}
                                                </td>
                                                <td className="px-4 py-4 text-stone-600">
                                                    {connection.latestSnapshot?.readinessScore === null ||
                                                    connection.latestSnapshot?.readinessScore === undefined
                                                        ? 'No readiness'
                                                        : `${Math.round(connection.latestSnapshot.readinessScore)}/100`}
                                                </td>
                                                <td className="px-4 py-4 text-stone-600">
                                                    {formatSleepHours(connection.latestSnapshot?.sleepHours ?? null)}
                                                </td>
                                                <td className="px-4 py-4 text-stone-600">{connection.latestSnapshot?.strainScore ?? 'N/A'}</td>
                                                <td className="px-4 py-4 text-stone-600">{connection.lastSyncedAt ?? 'Never synced'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
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
                                                    {nextSession.videoUrl && (
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                            className="mt-4 rounded-full border-stone-200 bg-white/80"
                                                        >
                                                            <Link href={route('training.index')}>
                                                                <PlayCircle className="size-4" />
                                                                Video attached
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2 rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-medium text-stone-800">
                                                    <Clock3 className="size-4" />
                                                    {nextSession.exercises.length} exercise{nextSession.exercises.length === 1 ? '' : 's'}
                                                </div>
                                            </div>
                                            <div className="mt-5">
                                                <WorkspaceTable minWidth="min-w-[760px]">
                                                    <WorkspaceTableHeader labels={['Exercise', 'Sets', 'Reps / time', 'Load', 'Rest', 'Target']} />
                                                    {nextSession.exercises.length === 0 ? (
                                                        <WorkspaceTableEmpty message="No exercise prescriptions are attached to this session yet." colSpan={6} />
                                                    ) : (
                                                        <tbody className="divide-y divide-stone-200">
                                                            {nextSession.exercises.map((exercise) => (
                                                                <tr key={`${nextSession.title}-${exercise.name}`} className="align-top">
                                                                    <td className="px-4 py-4">
                                                                        <p className="font-medium text-stone-950">{exercise.name}</p>
                                                                        {exercise.note && (
                                                                            <p className="mt-1 max-w-[260px] text-xs leading-5 text-stone-500">
                                                                                {exercise.note}
                                                                            </p>
                                                                        )}
                                                                    </td>
                                                                    <td className="px-4 py-4 text-stone-600">{exercise.sets ?? 'N/A'}</td>
                                                                    <td className="px-4 py-4 text-stone-600">
                                                                        {exercise.reps ?? exercise.prescription ?? 'N/A'}
                                                                    </td>
                                                                    <td className="px-4 py-4 text-stone-600">{exercise.load ?? 'N/A'}</td>
                                                                    <td className="px-4 py-4 text-stone-600">
                                                                        {formatRestLabel(exercise.rest_seconds, exercise.rest_label) ?? 'N/A'}
                                                                    </td>
                                                                    <td className="px-4 py-4 text-stone-600">{exercise.target ?? 'N/A'}</td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    )}
                                                </WorkspaceTable>
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
                                contentClassName="p-0"
                            >
                                <WorkspaceTable minWidth="min-w-[680px]">
                                    <WorkspaceTableHeader labels={['Session', 'Date', 'Focus', 'Exercises']} />
                                    {athlete.training?.upcomingSessions.length ? (
                                        <tbody className="divide-y divide-stone-200">
                                            {athlete.training.upcomingSessions.map((session) => (
                                                <tr key={`${session.title}-${session.scheduledDate ?? 'date'}`} className="align-top">
                                                    <td className="px-4 py-4 font-medium text-stone-950">{session.title}</td>
                                                    <td className="px-4 py-4 text-stone-600">
                                                        {session.scheduledDate ? shortDayLabel(session.scheduledDate) : 'No date'}
                                                    </td>
                                                    <td className="px-4 py-4 text-stone-600">{session.focus ?? 'No focus set'}</td>
                                                    <td className="px-4 py-4">
                                                        <Badge variant="outline">{session.exerciseCount} exercises</Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    ) : (
                                        <WorkspaceTableEmpty message="No sessions are queued after the next one yet." colSpan={4} />
                                    )}
                                </WorkspaceTable>
                            </AthletePanel>

                            <AthletePanel
                                title="Recent logs"
                                description="Your last few logged sessions. This is the compliance trail."
                                contentClassName="p-0"
                            >
                                <WorkspaceTable minWidth="min-w-[720px]">
                                    <WorkspaceTableHeader labels={['Session', 'Status', 'Performed', 'Duration', 'RPE']} />
                                    {athlete.training?.recentLogs.length ? (
                                        <tbody className="divide-y divide-stone-200">
                                            {athlete.training.recentLogs.map((log) => (
                                                <tr key={`${log.sessionTitle}-${log.performedAt ?? 'pending'}`} className="align-top">
                                                    <td className="px-4 py-4 font-medium text-stone-950">{log.sessionTitle}</td>
                                                    <td className="px-4 py-4">
                                                        <Badge variant={badgeVariantForStatus(log.completionStatus)}>
                                                            {humanizeStatus(log.completionStatus)}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-4 text-stone-600">
                                                        {log.performedAt ? shortDayLabel(log.performedAt) : 'Not logged'}
                                                    </td>
                                                    <td className="px-4 py-4 text-stone-600">
                                                        {log.durationMinutes ? `${log.durationMinutes} min` : 'N/A'}
                                                    </td>
                                                    <td className="px-4 py-4 text-stone-600">
                                                        {log.exertionRating ? `RPE ${log.exertionRating}` : 'N/A'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    ) : (
                                        <WorkspaceTableEmpty message="No workout logs yet. The block only matters if you log the work." colSpan={5} />
                                    )}
                                </WorkspaceTable>
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
                            contentClassName="p-0"
                        >
                            <WorkspaceTable minWidth="min-w-[640px]">
                                <WorkspaceTableHeader labels={['Plan', 'Status', 'Time left', 'Renews on', 'Auto renew']} />
                                <tbody>
                                    <tr>
                                        <td className="px-4 py-4 font-medium text-stone-950">{athlete.membership.planName}</td>
                                        <td className="px-4 py-4">
                                            <Badge variant={badgeVariantForStatus(athlete.membership.status)}>
                                                {humanizeStatus(athlete.membership.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-4 text-stone-600">{formatDays(athlete.membership.daysRemaining)}</td>
                                        <td className="px-4 py-4 text-stone-600">{athlete.membership.renewsAt ?? 'Not scheduled'}</td>
                                        <td className="px-4 py-4 text-stone-600">{athlete.membership.autoRenew ? 'Enabled' : 'Disabled'}</td>
                                    </tr>
                                </tbody>
                            </WorkspaceTable>
                        </AthletePanel>

                        <AthletePanel
                            title="Coach loop"
                            description="Who you are assigned to and how the relationship is framed."
                            contentClassName="p-0"
                        >
                            <WorkspaceTable minWidth="min-w-[720px]">
                                <WorkspaceTableHeader labels={['Coach', 'Email', 'Relationship', 'Started', 'Goal']} />
                                {athlete.coaches.length === 0 ? (
                                    <WorkspaceTableEmpty message="No coach has been assigned yet." colSpan={5} />
                                ) : (
                                    <tbody className="divide-y divide-stone-200">
                                        {athlete.coaches.map((coachEntry) => (
                                            <tr key={coachEntry.id} className="align-top">
                                                <td className="px-4 py-4 font-medium text-stone-950">{coachEntry.name}</td>
                                                <td className="px-4 py-4 text-stone-600">{coachEntry.email}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant="outline">{humanizeStatus(coachEntry.relationshipStatus)}</Badge>
                                                </td>
                                                <td className="px-4 py-4 text-stone-600">{coachEntry.startDate ?? 'Not set'}</td>
                                                <td className="px-4 py-4 text-stone-600">
                                                    {coachEntry.goal ?? 'General coaching relationship'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </AthletePanel>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
