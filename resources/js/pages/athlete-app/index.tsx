import { AthleteAppShell } from '@/components/athlete-app-shell';
import { AthletePanel, ReadinessDial } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    Activity,
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Droplets,
    Dumbbell,
    Flame,
    Gauge,
    HeartPulse,
    Image,
    MessageCircle,
    Moon,
    Scale,
    ShieldCheck,
    TrendingUp,
    UserRound,
    Utensils,
    Video,
    Watch,
} from 'lucide-react';

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

interface ProgramSummary {
    id: number;
    title: string;
    goal: string | null;
    status: string;
    startDate: string | null;
    endDate: string | null;
    notes: string | null;
    coach: {
        id: number;
        name: string;
        email: string;
    };
    sessionCount: number;
    completedSessionCount: number;
    pendingSessionCount: number;
    nextSessionDate: string | null;
}

interface ScheduleDay {
    date: string;
    dayNumber: number;
    weekday: string;
    isToday: boolean;
    isSelected: boolean;
    sessionCount: number;
    completedCount: number;
    hasMedia: boolean;
}

interface SelectedDaySession {
    id: number;
    title: string;
    scheduledDate: string | null;
    focus: string | null;
    instructions: string | null;
    videoUrl: string | null;
    mediaCount: number;
    exerciseCount: number;
    exercisePreview: string[];
    completionStatus: string;
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
}

interface ChartPoint {
    date: string;
    value: number | null;
}

interface AthleteCharts {
    rangeDays: number;
    rangeOptions: number[];
    from: string;
    to: string;
    wearable: {
        readiness: ChartPoint[];
        strain: ChartPoint[];
        sleepHours: ChartPoint[];
        heartRateVariability: ChartPoint[];
        restingHeartRate: ChartPoint[];
        steps: ChartPoint[];
        caloriesBurned: ChartPoint[];
        activeMinutes: ChartPoint[];
    };
    progress: {
        weightKg: ChartPoint[];
        bodyFatPercentage: ChartPoint[];
        waistCm: ChartPoint[];
        caloriesConsumed: ChartPoint[];
        proteinGrams: ChartPoint[];
        carbsGrams: ChartPoint[];
        fatGrams: ChartPoint[];
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
    coaches: Array<{
        id: number;
        name: string;
        email: string;
        goal: string | null;
        status: string;
        startedAt: string | null;
    }>;
    programs: ProgramSummary[];
    schedule: {
        selectedDate: string;
        month: string;
        monthLabel: string;
        previousMonth: string;
        nextMonth: string;
        days: ScheduleDay[];
    };
    selectedDaySessions: SelectedDaySession[];
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

function statusLabel(status: string) {
    return status.replace(/_/g, ' ');
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
                        Coach: {props.coach?.name ?? props.coaches[0]?.name ?? 'Not assigned yet'} · Programs: {props.programs.length || 'No active blocks'}
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

function ProgramsPanel({ programs }: { programs: ProgramSummary[] }) {
    return (
        <AthletePanel
            title="Assigned programs"
            description="Every active or draft block assigned to you. Open one to see sessions, exercises, videos, images, and logs."
            className="scroll-mt-8"
        >
            <div id="programs" className="-mt-20 pt-20" />
            {programs.length === 0 ? (
                <div className="rounded-[1.5rem] border border-dashed border-stone-300 bg-stone-50 p-5">
                    <p className="font-medium text-stone-950">No assigned programs yet.</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">When your coach assigns a program, it will appear here with its schedule and media.</p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-[1.35rem] border border-stone-200 bg-white">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="bg-stone-50 text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                <tr>
                                    <th className="px-4 py-3">Program</th>
                                    <th className="px-4 py-3">Coach</th>
                                    <th className="px-4 py-3">Dates</th>
                                    <th className="px-4 py-3">Sessions</th>
                                    <th className="px-4 py-3">Next</th>
                                    <th className="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100">
                                {programs.map((program) => (
                                    <tr key={program.id} className="align-top">
                                        <td className="px-4 py-4">
                                            <Link href={route('athlete.programs.show', program.id)} className="font-semibold text-stone-950 hover:text-emerald-800">
                                                {program.title}
                                            </Link>
                                            <p className="mt-1 text-stone-600">{program.goal ?? 'No goal written yet.'}</p>
                                            <Badge variant="outline" className="mt-2 capitalize">
                                                {statusLabel(program.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-4 text-stone-700">{program.coach.name}</td>
                                        <td className="px-4 py-4 text-stone-700">
                                            {formatDate(program.startDate)}
                                            <span className="block text-stone-400">to {formatDate(program.endDate)}</span>
                                        </td>
                                        <td className="px-4 py-4 text-stone-700">
                                            <span className="font-semibold text-stone-950">{program.completedSessionCount}</span> / {program.sessionCount} complete
                                            {program.pendingSessionCount > 0 && <span className="block text-amber-700">{program.pendingSessionCount} due</span>}
                                        </td>
                                        <td className="px-4 py-4 text-stone-700">{formatDate(program.nextSessionDate)}</td>
                                        <td className="px-4 py-4 text-right">
                                            <Button asChild size="sm" variant="outline">
                                                <Link href={route('athlete.programs.show', program.id)}>Open</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </AthletePanel>
    );
}

function CalendarPanel({ schedule, sessions, rangeDays }: { schedule: AthleteAppHomeProps['schedule']; sessions: SelectedDaySession[]; rangeDays: number }) {
    const selectedDate = formatDate(schedule.selectedDate);
    const monthHref = (month: string) => `/app?month=${month}&date=${month}-01&range=${rangeDays}`;
    const dayHref = (day: ScheduleDay) => `/app?month=${schedule.month}&date=${day.date}&range=${rangeDays}`;

    return (
        <AthletePanel
            title="Calendar and daily schedule"
            description="Pick a day to see assigned workouts only for that date. This is your client schedule, not the admin dashboard."
            className="scroll-mt-8"
        >
            <div id="schedule" className="-mt-20 pt-20" />
            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">Selected day</p>
                        <p className="mt-1 text-xl font-semibold tracking-[-0.03em] text-stone-950">{selectedDate}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={monthHref(schedule.previousMonth)}>
                                <ChevronLeft className="size-4" />
                                Previous
                            </Link>
                        </Button>
                        <Badge variant="outline" className="rounded-full px-3 py-1.5 text-stone-700">
                            {schedule.monthLabel}
                        </Badge>
                        <Button asChild variant="outline" size="sm">
                            <Link href={monthHref(schedule.nextMonth)}>
                                Next
                                <ChevronRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-7 gap-2">
                    {schedule.days.map((day) => (
                        <Link
                            key={day.date}
                            href={dayHref(day)}
                            preserveScroll
                            preserveState
                            className={[
                                'min-h-20 rounded-2xl border p-2 text-left transition',
                                day.isSelected
                                    ? 'border-emerald-700 bg-emerald-700 text-white shadow-[0_14px_36px_-25px_rgba(4,120,87,0.8)]'
                                    : day.sessionCount > 0
                                      ? 'border-emerald-200 bg-emerald-50 text-stone-950 hover:border-emerald-400'
                                      : 'border-stone-200 bg-white text-stone-500 hover:border-stone-300',
                            ].join(' ')}
                        >
                            <span className="block text-[0.64rem] font-semibold tracking-[0.18em] uppercase opacity-70">{day.weekday}</span>
                            <span className="mt-1 block text-lg font-semibold">{day.dayNumber}</span>
                            <span className="mt-1 flex flex-wrap items-center gap-1 text-[0.68rem] font-medium">
                                {day.sessionCount > 0 ? `${day.sessionCount} workout${day.sessionCount === 1 ? '' : 's'}` : 'Rest'}
                                {day.hasMedia && <Video className="size-3" />}
                            </span>
                            {day.isToday && <span className="mt-1 block text-[0.68rem] font-semibold">Today</span>}
                        </Link>
                    ))}
                </div>

                <div className="overflow-hidden rounded-[1.35rem] border border-stone-200 bg-white">
                    <div className="border-b border-stone-100 bg-stone-50 px-4 py-3">
                        <p className="font-semibold text-stone-950">Workouts on {selectedDate}</p>
                    </div>
                    {sessions.length === 0 ? (
                        <div className="p-5 text-sm leading-6 text-stone-600">No scheduled workout for this day.</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[860px] text-left text-sm">
                                <thead className="bg-white text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Workout</th>
                                        <th className="px-4 py-3">Coach</th>
                                        <th className="px-4 py-3">Program</th>
                                        <th className="px-4 py-3">Sets / reps</th>
                                        <th className="px-4 py-3">Media</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100">
                                    {sessions.map((session) => (
                                        <tr key={session.id} className="align-top">
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-stone-950">{session.title}</p>
                                                <p className="mt-1 text-stone-600">{session.focus ?? 'Training session'}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-700">{session.coach.name}</td>
                                            <td className="px-4 py-4 text-stone-700">{session.program.title}</td>
                                            <td className="px-4 py-4 text-stone-700">
                                                {session.exercisePreview.length > 0 ? (
                                                    <ul className="space-y-1">
                                                        {session.exercisePreview.map((line) => (
                                                            <li key={line}>{line}</li>
                                                        ))}
                                                    </ul>
                                                ) : (
                                                    'No exercises entered'
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-stone-700">
                                                <div className="flex items-center gap-2">
                                                    {session.videoUrl && <Video className="size-4 text-emerald-700" />}
                                                    {session.mediaCount > 0 && <Image className="size-4 text-amber-600" />}
                                                    {session.videoUrl || session.mediaCount > 0 ? `${session.mediaCount} item${session.mediaCount === 1 ? '' : 's'}` : 'None'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={session.completionStatus === 'completed' ? 'default' : 'outline'} className="capitalize">
                                                    {statusLabel(session.completionStatus)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <Button asChild size="sm" className="bg-emerald-800 text-white hover:bg-emerald-900">
                                                    <Link href={route('athlete.workouts.show', session.id)}>
                                                        {session.completionStatus === 'scheduled' ? 'Start' : 'Open'}
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AthletePanel>
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

function validChartPoints(points: ChartPoint[]) {
    return points.filter((point): point is ChartPoint & { value: number } => typeof point.value === 'number' && Number.isFinite(point.value));
}

function chartStats(points: ChartPoint[]) {
    const validPoints = validChartPoints(points);

    if (validPoints.length === 0) {
        return {
            latest: null,
            latestDate: null,
            average: null,
            min: 0,
            max: 1,
            delta: null,
            dataCount: 0,
            totalCount: points.length,
            coverage: 0,
        };
    }

    const values = validPoints.map((point) => point.value);
    const latestPoint = validPoints[validPoints.length - 1];
    const firstPoint = validPoints[0];
    const average = values.reduce((sum, value) => sum + value, 0) / values.length;
    const min = Math.min(...values);
    const max = Math.max(...values);

    return {
        latest: latestPoint.value,
        latestDate: latestPoint.date,
        average,
        min: min === max ? min - 1 : min,
        max: min === max ? max + 1 : max,
        delta: latestPoint.value - firstPoint.value,
        dataCount: validPoints.length,
        totalCount: points.length,
        coverage: points.length === 0 ? 0 : Math.round((validPoints.length / points.length) * 100),
    };
}

function formatChartNumber(value: number | null, suffix = '') {
    if (value === null || Number.isNaN(value)) {
        return 'N/A';
    }

    const maximumFractionDigits = Math.abs(value) >= 100 ? 0 : 1;
    const formatted = new Intl.NumberFormat(undefined, { maximumFractionDigits }).format(value);

    return `${formatted}${suffix}`;
}

function formatShortDate(value: string | null) {
    if (!value) {
        return 'No date';
    }

    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(new Date(`${value}T00:00:00`));
}

function LineChart({ points, stroke = '#047857' }: { points: ChartPoint[]; stroke?: string }) {
    const stats = chartStats(points);
    const denominator = Math.max(points.length - 1, 1);
    const lastDataIndex = points.reduce((last, point, index) => (typeof point.value === 'number' ? index : last), -1);
    const markerStep = points.length > 60 ? 12 : points.length > 30 ? 7 : points.length > 14 ? 4 : 1;
    const segments: string[][] = [];
    let currentSegment: string[] = [];

    points.forEach((point, index) => {
        if (typeof point.value !== 'number') {
            if (currentSegment.length > 0) {
                segments.push(currentSegment);
                currentSegment = [];
            }

            return;
        }

        const x = points.length === 1 ? 50 : (index / denominator) * 100;
        const y = 92 - ((point.value - stats.min) / (stats.max - stats.min)) * 78;

        currentSegment.push(`${x},${y}`);
    });

    if (currentSegment.length > 0) {
        segments.push(currentSegment);
    }

    return (
        <svg viewBox="0 0 100 100" className="h-24 w-full overflow-visible">
            <path d="M0 92 H100" stroke="#e7e5e4" strokeWidth="1" />
            <path d="M0 52 H100" stroke="#f1f0ec" strokeWidth="1" />
            <path d="M0 14 H100" stroke="#f1f0ec" strokeWidth="1" />
            {segments.length > 0 && (
                <>
                    {segments.map((segment) => (
                        <polyline key={segment.join(' ')} points={segment.join(' ')} fill="none" stroke={stroke} strokeLinecap="round" strokeLinejoin="round" strokeWidth="4" />
                    ))}
                    {points.map((point, index) => {
                        if (typeof point.value !== 'number' || (index !== lastDataIndex && index % markerStep !== 0)) {
                            return null;
                        }

                        const x = points.length === 1 ? 50 : (index / denominator) * 100;
                        const y = 92 - ((point.value - stats.min) / (stats.max - stats.min)) * 78;

                        return <circle key={`${point.date}-${point.value}-${index}`} cx={x} cy={y} r={index === lastDataIndex ? '3.4' : '2.3'} fill="white" stroke={stroke} strokeWidth="2" />;
                    })}
                </>
            )}
        </svg>
    );
}

function BarChart({ points, fill = '#0f766e' }: { points: ChartPoint[]; fill?: string }) {
    const stats = chartStats(points);
    const max = Math.max(stats.max, 1);

    return (
        <svg viewBox="0 0 100 100" className="h-24 w-full overflow-visible">
            <path d="M0 92 H100" stroke="#e7e5e4" strokeWidth="1" />
            {points.map((point, index) => {
                if (typeof point.value !== 'number') {
                    return null;
                }

                const gap = points.length > 60 ? 0.35 : points.length > 30 ? 0.55 : points.length > 20 ? 0.8 : 1.5;
                const width = points.length === 1 ? 8 : Math.max(1, (92 - gap * (points.length - 1)) / points.length);
                const x = points.length === 1 ? 46 : 4 + index * (width + gap);
                const height = Math.max(3, (point.value / max) * 76);

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
    icon: Icon = TrendingUp,
}: {
    title: string;
    points: ChartPoint[];
    unit?: string;
    type?: 'line' | 'bar';
    accent?: string;
    icon?: typeof Dumbbell;
}) {
    const stats = chartStats(points);
    const trend = stats.delta === null ? 'No trend yet' : stats.delta > 0 ? `+${formatChartNumber(stats.delta, unit)}` : formatChartNumber(stats.delta, unit);
    const trendClass = stats.delta === null ? 'text-stone-500' : stats.delta >= 0 ? 'text-emerald-700' : 'text-red-700';

    return (
        <div className="rounded-[1.55rem] border border-stone-200 bg-white p-4 shadow-[0_18px_45px_-38px_rgba(68,64,60,0.45)]">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="flex items-center gap-2">
                        <span className="grid size-8 place-items-center rounded-full bg-stone-100">
                            <Icon className="size-4" style={{ color: accent }} />
                        </span>
                        <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">{title}</p>
                    </div>
                    <p className="mt-2 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{formatChartNumber(stats.latest, unit)}</p>
                    <p className="mt-1 text-xs text-stone-500">Latest {formatShortDate(stats.latestDate)}</p>
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
            <div className="mt-3 grid grid-cols-3 gap-2 text-xs">
                <div className="rounded-xl bg-stone-50 px-3 py-2">
                    <span className="block text-stone-400">Min / max</span>
                    <span className="font-semibold text-stone-700">
                        {formatChartNumber(stats.dataCount ? stats.min : null, unit)} / {formatChartNumber(stats.dataCount ? stats.max : null, unit)}
                    </span>
                </div>
                <div className="rounded-xl bg-stone-50 px-3 py-2">
                    <span className="block text-stone-400">Trend</span>
                    <span className={`font-semibold ${trendClass}`}>{trend}</span>
                </div>
                <div className="rounded-xl bg-stone-50 px-3 py-2">
                    <span className="block text-stone-400">Coverage</span>
                    <span className="font-semibold text-stone-700">
                        {stats.dataCount}/{stats.totalCount} days
                    </span>
                </div>
            </div>
        </div>
    );
}

function ChartSection({ title, description, children }: { title: string; description: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <div>
                <h3 className="font-semibold tracking-[-0.03em] text-stone-950">{title}</h3>
                <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p>
            </div>
            <div className="grid gap-3 md:grid-cols-2">{children}</div>
        </section>
    );
}

function HealthTrendsPanel({ charts, schedule }: { charts: AthleteCharts; schedule: AthleteAppHomeProps['schedule'] }) {
    const changeRange = (range: number) => {
        router.get(
            '/app',
            {
                range,
                date: schedule.selectedDate,
                month: schedule.month,
            },
            { only: ['charts'], preserveScroll: true, preserveState: true, replace: true },
        );
    };

    return (
        <AthletePanel
            title="Health trends"
            description={`Dynamic range from ${formatDate(charts.from)} to ${formatDate(charts.to)}. Recovery, activity, food, body, and check-in trends all refresh without leaving the app.`}
        >
            <div className="space-y-4">
                <div className="flex flex-col gap-3 rounded-[1.35rem] border border-stone-200 bg-stone-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">Range</p>
                        <p className="mt-1 text-sm text-stone-600">Switch between weekly, monthly, and 3-month trends.</p>
                    </div>
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
                </div>

                <ChartSection title="Recovery" description="WHOOP-style signals that explain whether the athlete should push, control effort, or recover.">
                    <TrendCard title="Readiness" points={charts.wearable.readiness} unit="%" accent="#047857" icon={HeartPulse} />
                    <TrendCard title="Sleep" points={charts.wearable.sleepHours} unit="h" accent="#2563eb" icon={Moon} />
                    <TrendCard title="Strain" points={charts.wearable.strain} accent="#f59e0b" icon={Activity} />
                    <TrendCard title="HRV" points={charts.wearable.heartRateVariability} unit="ms" accent="#0d9488" icon={HeartPulse} />
                    <TrendCard title="Resting HR" points={charts.wearable.restingHeartRate} unit=" bpm" accent="#dc2626" icon={Gauge} />
                </ChartSection>

                <ChartSection title="Activity" description="Daily movement and output from the wearable stream.">
                    <TrendCard title="Steps" points={charts.wearable.steps} type="bar" accent="#16a34a" icon={Activity} />
                    <TrendCard title="Burned calories" points={charts.wearable.caloriesBurned} type="bar" accent="#ea580c" icon={Flame} />
                    <TrendCard title="Active minutes" points={charts.wearable.activeMinutes} unit="m" type="bar" accent="#0891b2" icon={Watch} />
                </ChartSection>

                <ChartSection title="Nutrition and hydration" description="Manual food and water check-ins so coaching decisions are not based only on the watch.">
                    <TrendCard title="Calories eaten" points={charts.progress.caloriesConsumed} type="bar" accent="#f97316" icon={Flame} />
                    <TrendCard title="Protein" points={charts.progress.proteinGrams} unit="g" type="bar" accent="#65a30d" icon={Utensils} />
                    <TrendCard title="Carbs" points={charts.progress.carbsGrams} unit="g" type="bar" accent="#eab308" icon={Utensils} />
                    <TrendCard title="Fat" points={charts.progress.fatGrams} unit="g" type="bar" accent="#a16207" icon={Utensils} />
                    <TrendCard title="Hydration" points={charts.progress.waterLiters} unit="L" type="bar" accent="#0284c7" icon={Droplets} />
                </ChartSection>

                <ChartSection title="Body and daily feel" description="Body metrics plus the subjective scores that explain why a session felt strong or rough.">
                    <TrendCard title="Weight" points={charts.progress.weightKg} unit="kg" accent="#44403c" icon={Scale} />
                    <TrendCard title="Body fat" points={charts.progress.bodyFatPercentage} unit="%" accent="#78716c" icon={Scale} />
                    <TrendCard title="Waist" points={charts.progress.waistCm} unit="cm" accent="#57534e" icon={Scale} />
                    <TrendCard title="Energy" points={charts.progress.energyScore} unit="/10" accent="#16a34a" icon={TrendingUp} />
                    <TrendCard title="Soreness" points={charts.progress.sorenessScore} unit="/10" accent="#be123c" icon={Dumbbell} />
                    <TrendCard title="Stress" points={charts.progress.stressScore} unit="/10" accent="#7c3aed" icon={HeartPulse} />
                    <TrendCard title="Sleep quality" points={charts.progress.sleepQualityScore} unit="/10" accent="#2563eb" icon={Moon} />
                </ChartSection>
            </div>
        </AthletePanel>
    );
}

export default function AthleteAppHome(props: AthleteAppHomeProps) {
    const readiness = props.wearable.latestSnapshot?.readinessScore ?? null;

    return (
        <AthleteAppShell
            active="app"
            unreadMessages={props.feed.unreadMessages}
            unreadNotifications={props.feed.unreadNotifications}
        >
            <Head title="Athlete app" />

            <div className="mx-auto max-w-6xl space-y-6">
                <TopHeader props={props} />

                <main className="grid gap-6 px-4 md:px-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <div className="space-y-6">
                        <CalendarPanel schedule={props.schedule} sessions={props.selectedDaySessions} rangeDays={props.charts.rangeDays} />

                        <ProgramsPanel programs={props.programs} />

                        <TodayWorkoutCard session={props.training.todaySession} />

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

                        <HealthTrendsPanel charts={props.charts} schedule={props.schedule} />

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
