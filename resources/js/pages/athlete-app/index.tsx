import { AthleteAppShell } from '@/components/athlete-app-shell';
import { AthletePanel, ReadinessDial } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, CalendarDays, CheckCircle2, Dumbbell, HeartPulse, MessageCircle, ShieldCheck, UserRound, Watch } from 'lucide-react';

interface WorkoutSetLogRow {
    exerciseIndex: number;
    exerciseName: string;
    setNumber: number;
    targetReps: string | null;
    targetLoad: string | null;
    targetRestSeconds: number | null;
    actualReps: string | null;
    actualLoad: string | null;
    actualRpe: number | null;
    completedAt: string | null;
    notes: string | null;
}

interface ExerciseRow {
    name: string;
    prescription: string | null;
    sets: number | null;
    reps: string | null;
    load: string | null;
    restSeconds: number | null;
    restLabel: string | null;
    target: string | null;
    note: string | null;
}

interface TodaySession {
    session: {
        id: number;
        title: string;
        scheduledDate: string | null;
        focus: string | null;
        instructions: string | null;
        videoUrl: string | null;
    };
    program: {
        id: number;
        title: string;
        goal: string | null;
        status: string;
    };
    coach: {
        id: number;
        name: string;
        email: string;
    };
    exercises: ExerciseRow[];
    setLogs: WorkoutSetLogRow[];
}

interface ChartPoint {
    date: string;
    value: number;
}

interface AthleteCharts {
    rangeDays: number;
    rangeOptions: number[];
    wearable: {
        readiness: ChartPoint[];
        strain: ChartPoint[];
        sleepHours: ChartPoint[];
        heartRateVariability: ChartPoint[];
        restingHeartRate: ChartPoint[];
        steps: ChartPoint[];
        caloriesBurned: ChartPoint[];
    };
    progress: {
        weightKg: ChartPoint[];
        proteinGrams: ChartPoint[];
        waterLiters: ChartPoint[];
        energyScore: ChartPoint[];
        sorenessScore: ChartPoint[];
        stressScore: ChartPoint[];
        sleepQualityScore: ChartPoint[];
    };
}

interface AthleteAppHomeProps {
    viewer: {
        id: number;
        name: string;
        email: string;
        goal: string | null;
    };
    coach: {
        id: number;
        name: string;
        email: string;
        goal: string | null;
        status: string;
        startedAt: string | null;
    } | null;
    training: {
        program: {
            id: number;
            title: string;
            goal: string | null;
            status: string;
            startDate: string | null;
            endDate: string | null;
        } | null;
        todaySession: TodaySession | null;
    };
    membership: {
        id: number;
        planName: string;
        status: string;
        startsAt: string | null;
        renewsAt: string | null;
        endsAt: string | null;
        effectiveEndDate: string | null;
        daysRemaining: number | null;
        autoRenew: boolean;
    } | null;
    wearable: {
        connectedCount: number;
        latestSnapshot: {
            metricDate: string | null;
            provider: string;
            readinessScore: number | null;
            readinessBand: string | null;
            strainScore: number | null;
            sleepHours: number | null;
            sleepNeedHours: number | null;
            caloriesBurned: number | null;
            steps: number | null;
            heartRateVariability: number | null;
            restingHeartRate: number | null;
        } | null;
    };
    progress: {
        loggedDate: string | null;
        weightKg: number | null;
        caloriesConsumed: number | null;
        proteinGrams: number | null;
        waterLiters: number | null;
        energyScore: number | null;
        sorenessScore: number | null;
        stressScore: number | null;
        sleepQualityScore: number | null;
    } | null;
    feed: {
        unreadNotifications: number;
        unreadMessages: number;
        items: Array<{
            id: number;
            title: string;
            body: string;
            actionLabel: string | null;
            actionUrl: string | null;
            publishedAt: string | null;
            read: boolean;
        }>;
    };
    charts: AthleteCharts;
}

function formatDate(value: string | null) {
    if (!value) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}

function metricValue(value: number | null | undefined, suffix = '') {
    return value === null || value === undefined ? 'N/A' : `${value}${suffix}`;
}

function exerciseLine(exercise: ExerciseRow) {
    const pieces = [
        exercise.sets && exercise.reps ? `${exercise.sets} x ${exercise.reps}` : exercise.prescription,
        exercise.load ? `Load ${exercise.load}` : null,
        exercise.restLabel ? `Rest ${exercise.restLabel}` : exercise.restSeconds ? `Rest ${exercise.restSeconds}s` : null,
    ].filter(Boolean);

    return pieces.join(' · ');
}

function TopHeader({ props }: { props: AthleteAppHomeProps }) {
    return (
        <section className="overflow-hidden rounded-b-[2.2rem] bg-emerald-800 px-5 pt-8 pb-7 text-white shadow-[0_28px_70px_-46px_rgba(6,78,59,0.9)] md:mx-6 md:mt-6 md:rounded-[2.2rem] md:px-8">
            <div className="flex items-start justify-between gap-4">
                <div className="space-y-2">
                    <Badge className="rounded-full bg-white/15 text-white hover:bg-white/15">Athlete app</Badge>
                    <h1 className="font-['Space_Grotesk'] text-3xl leading-tight font-bold tracking-[-0.04em] sm:text-5xl">
                        {props.viewer.name}
                    </h1>
                    <p className="max-w-2xl text-sm leading-6 text-emerald-50">
                        Coach: {props.coach?.name ?? 'Not assigned yet'} · Block: {props.training.program?.title ?? 'No active block'}
                    </p>
                </div>
                <div className="hidden rounded-3xl border border-white/15 bg-white/10 p-4 text-right md:block">
                    <p className="text-xs tracking-[0.22em] text-emerald-100 uppercase">Today</p>
                    <p className="mt-2 text-2xl font-semibold">{formatDate(new Date().toISOString().slice(0, 10))}</p>
                </div>
            </div>
        </section>
    );
}

function TodayWorkoutCard({ session }: { session: TodaySession | null }) {
    if (!session) {
        return (
            <AthletePanel title="Today workout" description="No assigned session is ready for today.">
                <div className="rounded-[1.5rem] border border-dashed border-stone-300 bg-stone-50 p-5">
                    <p className="font-medium text-stone-950">No workout scheduled.</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">
                        When your coach assigns a session, it will appear here with sets, reps, load, rest, and video.
                    </p>
                </div>
            </AthletePanel>
        );
    }

    return (
        <AthletePanel
            title="Today workout"
            description={`${session.session.focus ?? 'Training session'} · ${formatDate(session.session.scheduledDate)}`}
        >
            <div className="space-y-5">
                <div className="rounded-[1.7rem] bg-emerald-700 p-5 text-white">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm text-emerald-100">{session.program.title}</p>
                            <h2 className="mt-1 text-2xl font-bold tracking-[-0.04em]">{session.session.title}</h2>
                            {session.session.instructions && <p className="mt-3 max-w-2xl text-sm leading-6 text-emerald-50">{session.session.instructions}</p>}
                        </div>
                        <Button asChild className="bg-white text-emerald-950 hover:bg-emerald-50">
                            <Link href={route('athlete.workouts.show', session.session.id)}>
                                Start workout
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="space-y-3">
                    {session.exercises.slice(0, 4).map((exercise) => (
                        <div key={exercise.name} className="rounded-2xl border border-stone-200 bg-white p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="font-semibold text-stone-950">{exercise.name}</p>
                                    <p className="mt-1 text-sm text-stone-600">{exerciseLine(exercise) || 'No prescription entered'}</p>
                                </div>
                                <Badge variant="outline">{exercise.target ?? 'Work'}</Badge>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AthletePanel>
    );
}

function StatTile({ label, value, icon: Icon }: { label: string; value: string; icon: typeof Dumbbell }) {
    return (
        <div className="rounded-2xl border border-stone-200 bg-white p-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">{label}</p>
                <Icon className="size-4 text-emerald-700" />
            </div>
            <p className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{value}</p>
        </div>
    );
}

function chartStats(points: ChartPoint[]) {
    if (points.length === 0) {
        return { latest: null, average: null, min: 0, max: 1, delta: null };
    }

    const values = points.map((point) => Number(point.value));
    const latest = values[values.length - 1];
    const first = values[0];
    const average = values.reduce((sum, value) => sum + value, 0) / values.length;
    const min = Math.min(...values);
    const max = Math.max(...values);

    return {
        latest,
        average,
        min: min === max ? min - 1 : min,
        max: min === max ? max + 1 : max,
        delta: latest - first,
    };
}

function formatChartNumber(value: number | null, suffix = '') {
    if (value === null || Number.isNaN(value)) {
        return 'N/A';
    }

    return `${Number.isInteger(value) ? value : value.toFixed(1)}${suffix}`;
}

function LineChart({ points, stroke = '#047857' }: { points: ChartPoint[]; stroke?: string }) {
    const stats = chartStats(points);
    const chartPoints = points.map((point, index) => {
        const x = points.length === 1 ? 50 : (index / (points.length - 1)) * 100;
        const y = 92 - ((point.value - stats.min) / (stats.max - stats.min)) * 78;

        return `${x},${y}`;
    });

    return (
        <svg viewBox="0 0 100 100" className="h-24 w-full overflow-visible">
            <path d="M0 92 H100" stroke="#e7e5e4" strokeWidth="1" />
            <path d="M0 52 H100" stroke="#f1f0ec" strokeWidth="1" />
            <path d="M0 14 H100" stroke="#f1f0ec" strokeWidth="1" />
            {points.length > 0 && (
                <>
                    <polyline points={chartPoints.join(' ')} fill="none" stroke={stroke} strokeLinecap="round" strokeLinejoin="round" strokeWidth="4" />
                    {chartPoints.map((point, index) => {
                        const [x, y] = point.split(',');

                        return <circle key={`${point}-${index}`} cx={x} cy={y} r="2.5" fill="white" stroke={stroke} strokeWidth="2" />;
                    })}
                </>
            )}
        </svg>
    );
}

function BarChart({ points, fill = '#0f766e' }: { points: ChartPoint[]; fill?: string }) {
    const stats = chartStats(points);

    return (
        <svg viewBox="0 0 100 100" className="h-24 w-full overflow-visible">
            <path d="M0 92 H100" stroke="#e7e5e4" strokeWidth="1" />
            {points.map((point, index) => {
                const gap = points.length > 20 ? 0.8 : 1.5;
                const width = points.length === 1 ? 8 : Math.max(1, (92 - gap * (points.length - 1)) / points.length);
                const x = points.length === 1 ? 46 : 4 + index * (width + gap);
                const height = Math.max(5, ((point.value - stats.min) / (stats.max - stats.min)) * 72);

                return <rect key={`${point.date}-${point.value}`} x={x} y={92 - height} width={width} height={height} rx="2.8" fill={fill} />;
            })}
        </svg>
    );
}

function TrendCard({
    title,
    points,
    unit = '',
    type = 'line',
    accent = '#047857',
}: {
    title: string;
    points: ChartPoint[];
    unit?: string;
    type?: 'line' | 'bar';
    accent?: string;
}) {
    const stats = chartStats(points);
    const trend = stats.delta === null ? 'No trend yet' : stats.delta > 0 ? `+${formatChartNumber(stats.delta, unit)}` : formatChartNumber(stats.delta, unit);

    return (
        <div className="rounded-[1.55rem] border border-stone-200 bg-white p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">{title}</p>
                    <p className="mt-2 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{formatChartNumber(stats.latest, unit)}</p>
                </div>
                <Badge variant="outline" className="bg-stone-50">
                    Avg {formatChartNumber(stats.average, unit)}
                </Badge>
            </div>
            {points.length === 0 ? (
                <div className="mt-4 rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-4 text-sm text-stone-500">No data in this range.</div>
            ) : type === 'bar' ? (
                <BarChart points={points} fill={accent} />
            ) : (
                <LineChart points={points} stroke={accent} />
            )}
            <div className="mt-2 flex items-center justify-between text-xs text-stone-500">
                <span>{points[0]?.date ?? 'No start'}</span>
                <span>{trend}</span>
                <span>{points[points.length - 1]?.date ?? 'No end'}</span>
            </div>
        </div>
    );
}

function HealthTrendsPanel({ charts }: { charts: AthleteCharts }) {
    const changeRange = (range: number) => {
        router.get('/app', { range }, { only: ['charts'], preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <AthletePanel
            title="Health trends"
            description="Thirty-day view by default: recovery, sleep, strain, food, hydration, and body progress."
        >
            <div className="space-y-4">
                <div className="flex flex-wrap gap-2">
                    {charts.rangeOptions.map((range) => (
                        <Button
                            key={range}
                            type="button"
                            size="sm"
                            variant={charts.rangeDays === range ? 'default' : 'outline'}
                            className={charts.rangeDays === range ? 'bg-emerald-800 text-white hover:bg-emerald-900' : 'bg-white'}
                            onClick={() => changeRange(range)}
                        >
                            {range}D
                        </Button>
                    ))}
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                    <TrendCard title="Readiness" points={charts.wearable.readiness} unit="%" accent="#047857" />
                    <TrendCard title="Sleep" points={charts.wearable.sleepHours} unit="h" accent="#2563eb" />
                    <TrendCard title="Strain" points={charts.wearable.strain} accent="#f59e0b" />
                    <TrendCard title="HRV" points={charts.wearable.heartRateVariability} unit="ms" accent="#0d9488" />
                    <TrendCard title="Weight" points={charts.progress.weightKg} unit="kg" accent="#44403c" />
                    <TrendCard title="Protein" points={charts.progress.proteinGrams} unit="g" type="bar" accent="#65a30d" />
                    <TrendCard title="Hydration" points={charts.progress.waterLiters} unit="L" type="bar" accent="#0284c7" />
                    <TrendCard title="Calories" points={charts.wearable.caloriesBurned} type="bar" accent="#ea580c" />
                </div>
            </div>
        </AthletePanel>
    );
}

export default function AthleteAppHome(props: AthleteAppHomeProps) {
    const workoutHref = props.training.todaySession ? route('athlete.workouts.show', props.training.todaySession.session.id) : '/app';
    const readiness = props.wearable.latestSnapshot?.readinessScore ?? null;

    return (
        <AthleteAppShell
            active="feed"
            workoutHref={workoutHref}
            unreadMessages={props.feed.unreadMessages}
            unreadNotifications={props.feed.unreadNotifications}
        >
            <Head title="Athlete app" />

            <div className="mx-auto max-w-6xl space-y-6">
                <TopHeader props={props} />

                <main className="grid gap-6 px-4 md:px-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <div className="space-y-6">
                        <AthletePanel
                            title="Exertion and readiness"
                            description={props.wearable.latestSnapshot ? `Latest wearable sync from ${props.wearable.latestSnapshot.provider}.` : 'Connect WHOOP or another wearable to make this board useful.'}
                        >
                            {props.wearable.latestSnapshot ? (
                                <ReadinessDial
                                    score={readiness}
                                    label="Recovery signal"
                                    note={readiness === null ? 'Readiness not reported yet' : readiness >= 80 ? 'Green light' : readiness >= 60 ? 'Controlled push' : 'Recovery priority'}
                                    detail={`Sleep ${metricValue(props.wearable.latestSnapshot.sleepHours, 'h')} · Strain ${metricValue(props.wearable.latestSnapshot.strainScore)}`}
                                />
                            ) : (
                                <div className="rounded-[1.5rem] border border-dashed border-emerald-200 bg-emerald-50 p-5">
                                    <p className="font-semibold text-emerald-950">Connect your wearable.</p>
                                    <p className="mt-2 text-sm leading-6 text-emerald-900">
                                        Recovery, sleep, HRV, calories, and strain will show here after the first sync.
                                    </p>
                                    <Button asChild className="mt-4">
                                        <Link href="/wearables">Connect wearable</Link>
                                    </Button>
                                </div>
                            )}
                        </AthletePanel>

                        <HealthTrendsPanel charts={props.charts} />

                        <TodayWorkoutCard session={props.training.todaySession} />

                        <AthletePanel title="Feed" description="Coach updates, system notices, and anything that needs your attention." className="scroll-mt-8" contentClassName="space-y-3">
                            <div id="feed" className="-mt-20 pt-20" />
                            {props.feed.items.length === 0 ? (
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm text-stone-600">
                                    No updates right now.
                                </div>
                            ) : (
                                props.feed.items.map((item) => (
                                    <div key={item.id} className="rounded-2xl border border-stone-200 bg-white p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-semibold text-stone-950">{item.title}</p>
                                                <p className="mt-1 text-sm leading-6 text-stone-600">{item.body}</p>
                                            </div>
                                            {!item.read && <Badge className="bg-amber-200 text-stone-950 hover:bg-amber-200">New</Badge>}
                                        </div>
                                        {item.actionUrl && item.actionLabel && (
                                            <Button asChild variant="outline" size="sm" className="mt-3">
                                                <Link href={item.actionUrl}>{item.actionLabel}</Link>
                                            </Button>
                                        )}
                                    </div>
                                ))
                            )}
                        </AthletePanel>
                    </div>

                    <aside className="space-y-6">
                        <AthletePanel title="Membership" description="Your access window and renewal state.">
                            {props.membership ? (
                                <div className="grid gap-3">
                                    <StatTile label="Current plan" value={props.membership.planName} icon={ShieldCheck} />
                                    <StatTile label="Days left" value={props.membership.daysRemaining === null ? 'Open' : String(props.membership.daysRemaining)} icon={CalendarDays} />
                                    <StatTile label="Status" value={props.membership.status.replace(/_/g, ' ')} icon={CheckCircle2} />
                                    <p className="text-sm text-stone-600">Renews/ends: {formatDate(props.membership.effectiveEndDate)}</p>
                                </div>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm leading-6 text-stone-600">
                                    No membership found for this account yet.
                                </div>
                            )}
                        </AthletePanel>

                        <AthletePanel title="Progress snapshot" description="Latest body, food, hydration, and subjective scores.">
                            {props.progress ? (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <StatTile label="Weight" value={metricValue(props.progress.weightKg, ' kg')} icon={HeartPulse} />
                                    <StatTile label="Calories" value={metricValue(props.progress.caloriesConsumed)} icon={Dumbbell} />
                                    <StatTile label="Protein" value={metricValue(props.progress.proteinGrams, 'g')} icon={CheckCircle2} />
                                    <StatTile label="Water" value={metricValue(props.progress.waterLiters, 'L')} icon={Watch} />
                                    <StatTile label="Energy" value={metricValue(props.progress.energyScore, '/10')} icon={HeartPulse} />
                                    <StatTile label="Soreness" value={metricValue(props.progress.sorenessScore, '/10')} icon={Dumbbell} />
                                    <Button asChild variant="outline" className="sm:col-span-2">
                                        <Link href="/progress">Open full progress board</Link>
                                    </Button>
                                </div>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm leading-6 text-stone-600">
                                    No progress check-in yet. Your weight, food, sleep quality, soreness, and energy will appear after the first entry.
                                </div>
                            )}
                        </AthletePanel>

                        <AthletePanel title="Coach" description="Your active coach relationship.">
                            {props.coach ? (
                                <div className="space-y-4">
                                    <div className="flex items-center gap-3">
                                        <div className="grid size-12 place-items-center rounded-full bg-emerald-100 text-emerald-900">
                                            <UserRound className="size-5" />
                                        </div>
                                        <div>
                                            <p className="font-semibold text-stone-950">{props.coach.name}</p>
                                            <p className="text-sm text-stone-600">{props.coach.email}</p>
                                        </div>
                                    </div>
                                    <p className="text-sm leading-6 text-stone-600">{props.coach.goal ?? props.viewer.goal ?? 'No coach goal is written yet.'}</p>
                                    <Button asChild variant="outline">
                                        <Link href="/messages">
                                            Message coach
                                            <MessageCircle className="size-4" />
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm leading-6 text-stone-600">
                                    No coach assigned yet. Once an admin or coach links you, messaging and assigned programs appear here.
                                </div>
                            )}
                        </AthletePanel>
                    </aside>
                </main>
            </div>
        </AthleteAppShell>
    );
}
