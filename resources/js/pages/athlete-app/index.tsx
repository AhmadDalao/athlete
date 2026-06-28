import { AthleteAppShell } from '@/components/athlete-app-shell';
import { AthletePanel, ReadinessDial } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
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
