import { AthleteAppShell } from '@/components/athlete-app-shell';
import { AthletePanel } from '@/components/athlete-page-primitives';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock3, ExternalLink, FileText, Film, ListChecks, Pause, Play, SkipForward, TimerReset } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';

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

interface WorkoutSetLogRow {
    id: number | null;
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

interface WorkoutLogRow {
    id: number;
    completionStatus: string;
    performedAt: string | null;
    durationMinutes: number | null;
    exertionRating: number | null;
    energyScore: number | null;
    sorenessScore: number | null;
    stressScore: number | null;
    sleepQualityScore: number | null;
    notes: string | null;
}

interface WorkoutExecutionProps {
    execution: {
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
        workoutLog: WorkoutLogRow | null;
    };
}

interface SetInput {
    exercise_index: number;
    set_number: number;
    actual_reps: string;
    actual_load: string;
    actual_rpe: string;
    completed: boolean;
    notes: string;
}

interface SetFormData {
    sets: SetInput[];
}

interface JournalData {
    duration_minutes: string;
    exertion_rating: string;
    energy_score: string;
    soreness_score: string;
    stress_score: string;
    sleep_quality_score: string;
    notes: string;
}

function formatDate(value: string | null) {
    if (!value) {
        return 'Not scheduled';
    }

    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}

function formatTimer(seconds: number) {
    const safeSeconds = Math.max(0, seconds);
    const minutes = Math.floor(safeSeconds / 60);
    const remainder = safeSeconds % 60;

    return `${minutes}:${String(remainder).padStart(2, '0')}`;
}

function restLabel(exercise: ExerciseRow | null) {
    if (!exercise) {
        return '0:00';
    }

    if (exercise.restLabel) {
        return exercise.restLabel;
    }

    if (!exercise.restSeconds) {
        return 'No rest set';
    }

    return formatTimer(exercise.restSeconds);
}

function videoEmbedUrl(value: string | null) {
    if (!value) {
        return null;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.replace(/^www\./, '');

        if (host === 'youtu.be') {
            return `https://www.youtube.com/embed/${url.pathname.replace('/', '')}`;
        }

        if (host === 'youtube.com' || host === 'm.youtube.com') {
            const id = url.searchParams.get('v');

            if (id) {
                return `https://www.youtube.com/embed/${id}`;
            }

            if (url.pathname.startsWith('/shorts/')) {
                return `https://www.youtube.com/embed/${url.pathname.split('/')[2]}`;
            }
        }

        if (host === 'vimeo.com') {
            const id = url.pathname.split('/').filter(Boolean).at(0);

            if (id) {
                return `https://player.vimeo.com/video/${id}`;
            }
        }
    } catch {
        return null;
    }

    return null;
}

function isDirectVideo(value: string) {
    return /\.(mp4|webm|ogg)(\?.*)?$/i.test(value);
}

function VideoPlayer({ url }: { url: string | null }) {
    if (!url) {
        return (
            <div className="rounded-[1.5rem] border border-dashed border-stone-300 bg-stone-50 p-6 text-sm leading-6 text-stone-600">
                No video was attached to this session.
            </div>
        );
    }

    const embedUrl = videoEmbedUrl(url);

    if (embedUrl) {
        return (
            <div className="overflow-hidden rounded-[1.5rem] border border-stone-200 bg-stone-950">
                <iframe
                    src={embedUrl}
                    title="Workout video"
                    className="aspect-video w-full"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                />
            </div>
        );
    }

    if (isDirectVideo(url)) {
        return <video src={url} controls className="aspect-video w-full rounded-[1.5rem] bg-black" />;
    }

    return (
        <div className="rounded-[1.5rem] border border-stone-200 bg-white p-5">
            <p className="text-sm text-stone-600">This video cannot be embedded safely. Open it in a new tab.</p>
            <Button asChild variant="outline" className="mt-4">
                <a href={url} target="_blank" rel="noreferrer">
                    Open video
                    <ExternalLink className="size-4" />
                </a>
            </Button>
        </div>
    );
}

function tabClass(active: boolean) {
    return active ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50';
}

export default function WorkoutExecution({ execution }: WorkoutExecutionProps) {
    const [tab, setTab] = useState<'workout' | 'journal' | 'media'>('workout');
    const [currentExerciseIndex, setCurrentExerciseIndex] = useState(0);
    const [timerSeconds, setTimerSeconds] = useState(execution.exercises[0]?.restSeconds ?? 0);
    const [timerRunning, setTimerRunning] = useState(false);
    const currentExercise = execution.exercises[currentExerciseIndex] ?? null;

    const { data, setData, post, processing, errors } = useForm<SetFormData>({
        sets: execution.setLogs.map((row) => ({
            exercise_index: row.exerciseIndex,
            set_number: row.setNumber,
            actual_reps: row.actualReps ?? '',
            actual_load: row.actualLoad ?? '',
            actual_rpe: row.actualRpe ? String(row.actualRpe) : '',
            completed: Boolean(row.completedAt),
            notes: row.notes ?? '',
        })),
    });

    const [journal, setJournal] = useState<JournalData>({
        duration_minutes: execution.workoutLog?.durationMinutes ? String(execution.workoutLog.durationMinutes) : '',
        exertion_rating: execution.workoutLog?.exertionRating ? String(execution.workoutLog.exertionRating) : '',
        energy_score: execution.workoutLog?.energyScore ? String(execution.workoutLog.energyScore) : '',
        soreness_score: execution.workoutLog?.sorenessScore ? String(execution.workoutLog.sorenessScore) : '',
        stress_score: execution.workoutLog?.stressScore ? String(execution.workoutLog.stressScore) : '',
        sleep_quality_score: execution.workoutLog?.sleepQualityScore ? String(execution.workoutLog.sleepQualityScore) : '',
        notes: execution.workoutLog?.notes ?? '',
    });

    useEffect(() => {
        setTimerRunning(false);
        setTimerSeconds(currentExercise?.restSeconds ?? 0);
    }, [currentExerciseIndex, currentExercise?.restSeconds]);

    useEffect(() => {
        if (!timerRunning || timerSeconds <= 0) {
            return;
        }

        const interval = window.setInterval(() => {
            setTimerSeconds((value) => Math.max(0, value - 1));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [timerRunning, timerSeconds]);

    const currentSetRows = data.sets.filter((set) => set.exercise_index === currentExerciseIndex);
    const completedCount = data.sets.filter((set) => set.completed).length;
    const allCompleted = data.sets.length > 0 && completedCount === data.sets.length;

    const updateSet = (exerciseIndex: number, setNumber: number, patch: Partial<SetInput>) => {
        setData(
            'sets',
            data.sets.map((set) => (set.exercise_index === exerciseIndex && set.set_number === setNumber ? { ...set, ...patch } : set)),
        );
    };

    const savePartial = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('athlete.workouts.sets.store', execution.session.id), { preserveScroll: true });
    };

    const completeWorkout = (status: 'completed' | 'partial' | 'missed') => {
        router.post(
            route('athlete.workouts.complete', execution.session.id),
            {
                completion_status: status,
                performed_at: new Date().toISOString().slice(0, 10),
                duration_minutes: journal.duration_minutes,
                exertion_rating: journal.exertion_rating,
                energy_score: journal.energy_score,
                soreness_score: journal.soreness_score,
                stress_score: journal.stress_score,
                sleep_quality_score: journal.sleep_quality_score,
                notes: journal.notes,
            },
            { preserveScroll: true },
        );
    };

    return (
        <AthleteAppShell active="workout" workoutHref={route('athlete.workouts.show', execution.session.id)}>
            <Head title={execution.session.title} />

            <div className="mx-auto max-w-6xl space-y-6 px-4 py-6 md:px-6">
                <header className="rounded-[2rem] bg-emerald-800 p-5 text-white md:p-8">
                    <Button asChild variant="ghost" className="mb-5 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                        <Link href="/app">
                            <ArrowLeft className="size-4" />
                            Back to app
                        </Link>
                    </Button>
                    <div className="grid gap-5 md:grid-cols-[1fr_auto] md:items-end">
                        <div>
                            <Badge className="bg-white/15 text-white hover:bg-white/15">{execution.program.title}</Badge>
                            <h1 className="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-[-0.05em] md:text-5xl">
                                {execution.session.title}
                            </h1>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-emerald-50">
                                {execution.session.focus ?? 'Workout'} · Coach {execution.coach.name} · {formatDate(execution.session.scheduledDate)}
                            </p>
                        </div>
                        <div className="rounded-3xl border border-white/15 bg-white/10 p-4">
                            <p className="text-xs tracking-[0.22em] text-emerald-100 uppercase">Set completion</p>
                            <p className="mt-2 text-3xl font-semibold">
                                {completedCount}/{data.sets.length}
                            </p>
                        </div>
                    </div>
                </header>

                <div className="grid grid-cols-3 gap-2 rounded-[1.5rem] border border-stone-200 bg-white p-2">
                    <button type="button" onClick={() => setTab('workout')} className={`rounded-[1.05rem] px-4 py-3 text-sm font-semibold ${tabClass(tab === 'workout')}`}>
                        <ListChecks className="mr-2 inline size-4" />
                        Workout
                    </button>
                    <button type="button" onClick={() => setTab('journal')} className={`rounded-[1.05rem] px-4 py-3 text-sm font-semibold ${tabClass(tab === 'journal')}`}>
                        <FileText className="mr-2 inline size-4" />
                        Journal
                    </button>
                    <button type="button" onClick={() => setTab('media')} className={`rounded-[1.05rem] px-4 py-3 text-sm font-semibold ${tabClass(tab === 'media')}`}>
                        <Film className="mr-2 inline size-4" />
                        Media
                    </button>
                </div>

                {tab === 'workout' && (
                    <form className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]" onSubmit={savePartial}>
                        <AthletePanel title="Exercise list" description="Move through the coach prescription one exercise at a time.">
                            <div className="space-y-3">
                                {execution.exercises.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm text-stone-600">
                                        No exercises were added to this session.
                                    </div>
                                ) : (
                                    execution.exercises.map((exercise, index) => (
                                        <button
                                            key={`${exercise.name}-${index}`}
                                            type="button"
                                            onClick={() => setCurrentExerciseIndex(index)}
                                            className={`w-full rounded-2xl border p-4 text-left transition-colors ${
                                                currentExerciseIndex === index
                                                    ? 'border-emerald-300 bg-emerald-50'
                                                    : 'border-stone-200 bg-white hover:bg-stone-50'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-semibold text-stone-950">{exercise.name}</p>
                                                    <p className="mt-1 text-sm text-stone-600">
                                                        {exercise.sets && exercise.reps ? `${exercise.sets} x ${exercise.reps}` : exercise.prescription ?? 'No target'}
                                                    </p>
                                                </div>
                                                <Badge variant="outline">{exercise.target ?? 'Target'}</Badge>
                                            </div>
                                        </button>
                                    ))
                                )}
                            </div>
                        </AthletePanel>

                        <AthletePanel
                            title={currentExercise?.name ?? 'Workout execution'}
                            description={currentExercise?.note ?? execution.session.instructions ?? 'Log what you actually completed.'}
                        >
                            <div className="space-y-5">
                                <div className="rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4">
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.22em] text-stone-400 uppercase">Rest timer</p>
                                            <p className="mt-2 text-4xl font-semibold tracking-[-0.05em] text-stone-950">{formatTimer(timerSeconds)}</p>
                                            <p className="mt-1 text-sm text-stone-600">Prescribed: {restLabel(currentExercise)}</p>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" onClick={() => setTimerSeconds(currentExercise?.restSeconds ?? 0)}>
                                                <TimerReset className="size-4" />
                                                Reset
                                            </Button>
                                            <Button type="button" onClick={() => setTimerRunning((value) => !value)}>
                                                {timerRunning ? <Pause className="size-4" /> : <Play className="size-4" />}
                                                {timerRunning ? 'Pause' : 'Start'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div className="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
                                    <table className="w-full min-w-[860px] text-left text-sm">
                                        <thead className="bg-stone-50 text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                            <tr>
                                                <th className="px-4 py-3">Set</th>
                                                <th className="px-4 py-3">Target reps/time</th>
                                                <th className="px-4 py-3">Target load</th>
                                                <th className="px-4 py-3">Actual reps/time</th>
                                                <th className="px-4 py-3">Actual load</th>
                                                <th className="px-4 py-3">RPE</th>
                                                <th className="px-4 py-3">Done</th>
                                                <th className="px-4 py-3">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {currentSetRows.length === 0 ? (
                                                <tr>
                                                    <td colSpan={8} className="px-4 py-8 text-center text-stone-500">
                                                        No set rows for this exercise.
                                                    </td>
                                                </tr>
                                            ) : (
                                                currentSetRows.map((set) => (
                                                    <tr key={`${set.exercise_index}-${set.set_number}`} className="border-t border-stone-100">
                                                        <td className="px-4 py-3 font-semibold text-stone-950">{set.set_number}</td>
                                                        <td className="px-4 py-3 text-stone-600">
                                                            {execution.setLogs.find((row) => row.exerciseIndex === set.exercise_index && row.setNumber === set.set_number)
                                                                ?.targetReps ?? 'N/A'}
                                                        </td>
                                                        <td className="px-4 py-3 text-stone-600">
                                                            {execution.setLogs.find((row) => row.exerciseIndex === set.exercise_index && row.setNumber === set.set_number)
                                                                ?.targetLoad ?? 'N/A'}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <Input
                                                                value={set.actual_reps}
                                                                onChange={(event) =>
                                                                    updateSet(set.exercise_index, set.set_number, { actual_reps: event.target.value })
                                                                }
                                                                placeholder="8 or 40s"
                                                                className="h-10"
                                                            />
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <Input
                                                                value={set.actual_load}
                                                                onChange={(event) =>
                                                                    updateSet(set.exercise_index, set.set_number, { actual_load: event.target.value })
                                                                }
                                                                placeholder="120 kg"
                                                                className="h-10"
                                                            />
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <Input
                                                                type="number"
                                                                min="1"
                                                                max="10"
                                                                value={set.actual_rpe}
                                                                onChange={(event) =>
                                                                    updateSet(set.exercise_index, set.set_number, { actual_rpe: event.target.value })
                                                                }
                                                                placeholder="8"
                                                                className="h-10"
                                                            />
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <input
                                                                type="checkbox"
                                                                checked={set.completed}
                                                                onChange={(event) =>
                                                                    updateSet(set.exercise_index, set.set_number, { completed: event.target.checked })
                                                                }
                                                                className="size-5 rounded border-stone-300 text-emerald-700"
                                                            />
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <Input
                                                                value={set.notes}
                                                                onChange={(event) => updateSet(set.exercise_index, set.set_number, { notes: event.target.value })}
                                                                placeholder="Optional"
                                                                className="h-10"
                                                            />
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                                <InputError message={errors.sets} />

                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={currentExerciseIndex === 0}
                                            onClick={() => setCurrentExerciseIndex((value) => Math.max(0, value - 1))}
                                        >
                                            Previous
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={currentExerciseIndex >= execution.exercises.length - 1}
                                            onClick={() => setCurrentExerciseIndex((value) => Math.min(execution.exercises.length - 1, value + 1))}
                                        >
                                            Next
                                            <SkipForward className="size-4" />
                                        </Button>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button type="submit" variant="outline" disabled={processing}>
                                            Save partial
                                        </Button>
                                        <Button type="button" disabled={!allCompleted} onClick={() => completeWorkout('completed')}>
                                            <CheckCircle2 className="size-4" />
                                            Complete workout
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </AthletePanel>
                    </form>
                )}

                {tab === 'journal' && (
                    <AthletePanel title="Workout journal" description="Leave context your coach can actually use.">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Duration minutes</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="600"
                                    value={journal.duration_minutes}
                                    onChange={(event) => setJournal((value) => ({ ...value, duration_minutes: event.target.value }))}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Session RPE</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="10"
                                    value={journal.exertion_rating}
                                    onChange={(event) => setJournal((value) => ({ ...value, exertion_rating: event.target.value }))}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Energy</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="10"
                                    value={journal.energy_score}
                                    onChange={(event) => setJournal((value) => ({ ...value, energy_score: event.target.value }))}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Soreness</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="10"
                                    value={journal.soreness_score}
                                    onChange={(event) => setJournal((value) => ({ ...value, soreness_score: event.target.value }))}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Stress</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="10"
                                    value={journal.stress_score}
                                    onChange={(event) => setJournal((value) => ({ ...value, stress_score: event.target.value }))}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Sleep quality</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="10"
                                    value={journal.sleep_quality_score}
                                    onChange={(event) => setJournal((value) => ({ ...value, sleep_quality_score: event.target.value }))}
                                    className="mt-2"
                                />
                            </div>
                            <div className="sm:col-span-2 lg:col-span-4">
                                <label className="text-xs font-semibold tracking-[0.18em] text-stone-500 uppercase">Notes</label>
                                <Textarea
                                    value={journal.notes}
                                    onChange={(event) => setJournal((value) => ({ ...value, notes: event.target.value }))}
                                    placeholder="Pain, substitutions, missed work, technique notes, or anything your coach should know."
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="mt-5 flex flex-wrap gap-2">
                            <Button type="button" onClick={() => completeWorkout(allCompleted ? 'completed' : 'partial')}>
                                Save journal
                            </Button>
                            <Button type="button" variant="destructive" onClick={() => completeWorkout('missed')}>
                                Opt out / missed
                            </Button>
                            <Button asChild type="button" variant="outline">
                                <Link href="/progress">
                                    Open full progress
                                    <Clock3 className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </AthletePanel>
                )}

                {tab === 'media' && (
                    <AthletePanel title="Coach media" description="Session video, movement standard, or external coaching link.">
                        <VideoPlayer url={execution.session.videoUrl} />
                    </AthletePanel>
                )}
            </div>
        </AthleteAppShell>
    );
}
