import { AthleteAppShell } from '@/components/athlete-app-shell';
import { AthletePanel } from '@/components/athlete-page-primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, CheckCircle2, Dumbbell, Image, UserRound, Video } from 'lucide-react';

interface MediaItem {
    type: 'video' | 'image';
    url: string;
    title: string | null;
    isPrimary: boolean;
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

interface ProgramSession {
    id: number;
    title: string;
    scheduledDate: string | null;
    focus: string | null;
    instructions: string | null;
    videoUrl: string | null;
    mediaItems: MediaItem[];
    mediaCount: number;
    exerciseCount: number;
    exercisePreview: string[];
    exercises: ExerciseRow[];
    completionStatus: string;
    workoutLog: {
        id: number;
        completionStatus: string;
        performedAt: string | null;
        durationMinutes: number | null;
        exertionRating: number | null;
        notes: string | null;
    } | null;
}

interface ProgramDetail {
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
    sessions: ProgramSession[];
}

interface AthleteProgramProps {
    viewer: {
        id: number;
        name: string;
        email: string;
    };
    program: ProgramDetail;
}

function formatDate(value: string | null) {
    if (!value) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}

function statusLabel(value: string) {
    return value.replace(/_/g, ' ');
}

function exerciseLine(exercise: ExerciseRow) {
    return [
        exercise.sets && exercise.reps ? `${exercise.sets} x ${exercise.reps}` : exercise.prescription,
        exercise.load ? `Load ${exercise.load}` : null,
        exercise.restLabel ? `Rest ${exercise.restLabel}` : exercise.restSeconds ? `Rest ${exercise.restSeconds}s` : null,
        exercise.target,
    ]
        .filter(Boolean)
        .join(' · ');
}

export default function AthleteProgramShow({ program }: AthleteProgramProps) {
    const imageItems = program.sessions.flatMap((session) => session.mediaItems.filter((item) => item.type === 'image').map((item) => ({ ...item, session })));
    const videoCount = program.sessions.filter((session) => session.videoUrl).length;

    return (
        <AthleteAppShell active="programs">
            <Head title={`${program.title} program`} />

            <div className="mx-auto max-w-6xl space-y-6 px-4 py-5 md:px-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button asChild variant="outline" className="rounded-xl bg-white">
                        <Link href="/app#programs">
                            <ArrowLeft className="size-4" />
                            Back to app
                        </Link>
                    </Button>
                    <Badge variant="outline" className="capitalize">
                        {statusLabel(program.status)}
                    </Badge>
                </div>

                <section className="overflow-hidden rounded-[2rem] bg-emerald-800 p-6 text-white shadow-[0_28px_70px_-46px_rgba(6,78,59,0.9)] md:p-8">
                    <div className="grid gap-6 lg:grid-cols-[1.3fr_0.7fr] lg:items-end">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.22em] text-emerald-100 uppercase">Assigned program</p>
                            <h1 className="mt-3 font-['Space_Grotesk'] text-4xl leading-tight font-bold tracking-[-0.05em] md:text-5xl">{program.title}</h1>
                            <p className="mt-4 max-w-3xl text-sm leading-7 text-emerald-50">{program.goal ?? 'No program goal written yet.'}</p>
                        </div>
                        <div className="rounded-3xl border border-white/15 bg-white/10 p-5">
                            <div className="flex items-center gap-3">
                                <div className="grid size-11 place-items-center rounded-full bg-white/15">
                                    <UserRound className="size-5" />
                                </div>
                                <div>
                                    <p className="font-semibold">{program.coach.name}</p>
                                    <p className="text-sm text-emerald-100">{program.coach.email}</p>
                                </div>
                            </div>
                            <div className="mt-5 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p className="text-emerald-100">Start</p>
                                    <p className="font-semibold">{formatDate(program.startDate)}</p>
                                </div>
                                <div>
                                    <p className="text-emerald-100">End</p>
                                    <p className="font-semibold">{formatDate(program.endDate)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="grid gap-3 md:grid-cols-4">
                    <div className="rounded-2xl border border-stone-200 bg-white p-4">
                        <CalendarDays className="size-4 text-emerald-700" />
                        <p className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{program.sessionCount}</p>
                        <p className="text-sm text-stone-600">sessions assigned</p>
                    </div>
                    <div className="rounded-2xl border border-stone-200 bg-white p-4">
                        <CheckCircle2 className="size-4 text-emerald-700" />
                        <p className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{program.completedSessionCount}</p>
                        <p className="text-sm text-stone-600">completed sessions</p>
                    </div>
                    <div className="rounded-2xl border border-stone-200 bg-white p-4">
                        <Dumbbell className="size-4 text-emerald-700" />
                        <p className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{program.pendingSessionCount}</p>
                        <p className="text-sm text-stone-600">sessions needing logs</p>
                    </div>
                    <div className="rounded-2xl border border-stone-200 bg-white p-4">
                        <Video className="size-4 text-emerald-700" />
                        <p className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-stone-950">{videoCount}</p>
                        <p className="text-sm text-stone-600">video sessions</p>
                    </div>
                </div>

                <AthletePanel title="Program sessions" description="Open any row to execute the workout, record set completion, and review media.">
                    <div className="overflow-hidden rounded-[1.35rem] border border-stone-200 bg-white">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[980px] text-left text-sm">
                                <thead className="bg-stone-50 text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Date</th>
                                        <th className="px-4 py-3">Workout</th>
                                        <th className="px-4 py-3">Focus</th>
                                        <th className="px-4 py-3">Exercises</th>
                                        <th className="px-4 py-3">Media</th>
                                        <th className="px-4 py-3">Log</th>
                                        <th className="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100">
                                    {program.sessions.map((session) => (
                                        <tr key={session.id} className="align-top">
                                            <td className="px-4 py-4 text-stone-700">{formatDate(session.scheduledDate)}</td>
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-stone-950">{session.title}</p>
                                                {session.instructions && <p className="mt-1 max-w-sm text-stone-600">{session.instructions}</p>}
                                            </td>
                                            <td className="px-4 py-4 text-stone-700">{session.focus ?? 'Training'}</td>
                                            <td className="px-4 py-4 text-stone-700">
                                                {session.exercises.length === 0 ? (
                                                    'No exercises entered'
                                                ) : (
                                                    <div className="space-y-2">
                                                        {session.exercises.slice(0, 3).map((exercise) => (
                                                            <div key={`${session.id}-${exercise.name}`}>
                                                                <p className="font-medium text-stone-950">{exercise.name}</p>
                                                                <p className="text-stone-600">{exerciseLine(exercise) || 'No prescription entered'}</p>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-stone-700">
                                                <div className="flex items-center gap-2">
                                                    {session.videoUrl && <Video className="size-4 text-emerald-700" />}
                                                    {session.mediaItems.some((item) => item.type === 'image') && <Image className="size-4 text-amber-600" />}
                                                    {session.mediaCount > 0 ? `${session.mediaCount} item${session.mediaCount === 1 ? '' : 's'}` : 'None'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={session.completionStatus === 'completed' ? 'default' : 'outline'} className="capitalize">
                                                    {statusLabel(session.completionStatus)}
                                                </Badge>
                                                {session.workoutLog?.performedAt && <p className="mt-1 text-xs text-stone-500">{formatDate(session.workoutLog.performedAt)}</p>}
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
                    </div>
                </AthletePanel>

                <AthletePanel title="Media references" description="Images uploaded by the coach appear as quick visual references. Videos are opened from their workout rows.">
                    {imageItems.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm text-stone-600">No image references are attached to this program yet.</div>
                    ) : (
                        <div className="flex gap-4 overflow-x-auto pb-2">
                            {imageItems.map((item) => (
                                <a key={`${item.session.id}-${item.url}`} href={item.url} target="_blank" rel="noreferrer" className="min-w-72 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                                    <img src={item.url} alt={item.title ?? item.session.title} className="h-44 w-full object-cover" />
                                    <div className="p-4">
                                        <p className="font-semibold text-stone-950">{item.title ?? 'Workout reference'}</p>
                                        <p className="mt-1 text-sm text-stone-600">{item.session.title}</p>
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </AthletePanel>
            </div>
        </AthleteAppShell>
    );
}
