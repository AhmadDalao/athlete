import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading } from '@/components/athlete-page-primitives';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    Clock3,
    Dumbbell,
    NotebookText,
    PlayCircle,
    Plus,
    Timer,
    TrendingUp,
} from 'lucide-react';
import { Fragment, type FormEvent, useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Training',
        href: '/training',
    },
];

interface AthleteOption {
    id: number;
    name: string;
    email: string;
}

interface StatusOption {
    value: string;
    label: string;
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

interface WorkoutLogRow {
    completionStatus: string;
    performedAt: string | null;
    durationMinutes: number | null;
    exertionRating: number | null;
    notes: string | null;
}

interface TrainingSessionRow {
    id: number;
    title: string;
    scheduledDate: string | null;
    focus: string | null;
    instructions: string | null;
    videoUrl: string | null;
    exercises: ExerciseRow[];
    workoutLog: WorkoutLogRow | null;
}

interface TrainingProgramRow {
    id: number;
    title: string;
    status: string;
    goal: string | null;
    startDate: string | null;
    endDate: string | null;
    notes: string | null;
    coachName: string;
    athleteName: string;
    athleteId: number;
    sessionCount: number;
    nextSessionDate: string | null;
    sessions: TrainingSessionRow[];
}

interface TrainingPaginator {
    data: TrainingProgramRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface TrainingSummary {
    trackedPrograms: number;
    activePrograms: number;
    scheduledThisWeek: number;
    pendingLogs?: number;
    completedThisWeek?: number;
    loggedSessions?: number;
}

interface TrainingPageProps {
    viewerRole: string | null;
    scopeLabel: string;
    summary: TrainingSummary;
    programs: TrainingPaginator;
    rosterAthletes: AthleteOption[];
    canCreatePrograms: boolean;
    statusOptions: StatusOption[];
    exerciseFormatHint: string;
}

interface ProgramCreateFormData {
    athlete_id: string;
    title: string;
    goal: string;
    start_date: string;
    end_date: string;
    notes: string;
    first_session_title: string;
    first_session_date: string;
    first_session_focus: string;
    first_session_instructions: string;
    first_session_video_url: string;
    first_session_exercises: string;
}

interface SessionCreateFormData {
    title: string;
    scheduled_date: string;
    focus: string;
    instructions: string;
    video_url: string;
    exercises: string;
}

interface WorkoutLogFormData {
    completion_status: string;
    performed_at: string;
    duration_minutes: string;
    exertion_rating: string;
    notes: string;
}

interface ExerciseBuilderRow {
    name: string;
    sets: string;
    reps: string;
    load: string;
    rest: string;
    target: string;
    note: string;
}

const emptyExerciseBuilderRow = (): ExerciseBuilderRow => ({
    name: '',
    sets: '',
    reps: '',
    load: '',
    rest: '',
    target: '',
    note: '',
});

function serializeExerciseBuilderRows(rows: ExerciseBuilderRow[]) {
    return rows
        .filter((row) => Object.values(row).some((value) => value.trim() !== ''))
        .map((row) => [row.name, row.sets, row.reps, row.load, row.rest, row.target, row.note].map((value) => value.trim()).join(' | '))
        .join('\n');
}

function parseExerciseBuilderRows(value: string) {
    if (value.trim() === '') {
        return [emptyExerciseBuilderRow()];
    }

    return value
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
            const parts = line.split('|').map((part) => part.trim());

            if (parts.length >= 4) {
                return {
                    name: parts[0] ?? '',
                    sets: parts[1] ?? '',
                    reps: parts[2] ?? '',
                    load: parts[3] ?? '',
                    rest: parts[4] ?? '',
                    target: parts[5] ?? '',
                    note: parts[6] ?? '',
                };
            }

            return {
                name: parts[0] ?? '',
                sets: '',
                reps: parts[1] ?? '',
                load: '',
                rest: '',
                target: '',
                note: parts[2] ?? '',
            };
        });
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function badgeVariantForProgram(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'archived') {
        return 'outline';
    }

    if (status === 'completed') {
        return 'secondary';
    }

    if (status === 'draft') {
        return 'outline';
    }

    return 'default';
}

function badgeVariantForWorkout(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'missed') {
        return 'destructive';
    }

    if (status === 'completed') {
        return 'default';
    }

    if (status === 'partial' || status === 'skipped') {
        return 'secondary';
    }

    return 'outline';
}

function metricCards(viewerRole: string | null, summary: TrainingSummary) {
    const base = [
        {
            title: 'Tracked programs',
            value: summary.trackedPrograms.toString(),
            note: 'Programs visible in this workspace right now.',
            icon: Dumbbell,
        },
        {
            title: 'Active blocks',
            value: summary.activePrograms.toString(),
            note: 'Live programming, not old junk collecting dust.',
            icon: TrendingUp,
        },
        {
            title: 'Scheduled this week',
            value: summary.scheduledThisWeek.toString(),
            note: 'Sessions on deck over the next seven days.',
            icon: CalendarDays,
        },
    ];

    if (viewerRole === 'coach') {
        return [
            ...base,
            {
                title: 'Pending logs',
                value: String(summary.pendingLogs ?? 0),
                note: 'Past-due sessions without athlete feedback yet.',
                icon: AlertTriangle,
            },
        ];
    }

    if (viewerRole === 'athlete') {
        return [
            ...base,
            {
                title: 'Completed this week',
                value: String(summary.completedThisWeek ?? 0),
                note: 'Sessions you logged across the last seven days, which matters more than intentions.',
                icon: CheckCircle2,
            },
        ];
    }

    return [
        ...base,
        {
            title: 'Logged sessions',
            value: String(summary.loggedSessions ?? 0),
            note: 'Workout logs submitted across the visible platform scope.',
            icon: ClipboardList,
        },
    ];
}

function roleCallout(viewerRole: string | null) {
    if (viewerRole === 'coach') {
        return 'Build the block, assign it cleanly, and keep athlete compliance visible without chasing screenshots in WhatsApp.';
    }

    if (viewerRole === 'athlete') {
        return 'This is your side of the deal: follow the plan, log the session, and stop making your coach guess what happened.';
    }

    return 'Read-only platform visibility for programs, sessions, and athlete logging activity.';
}

function formatRest(value: number | null, label: string | null) {
    if (label) {
        return label;
    }

    if (value === null) {
        return null;
    }

    if (value >= 60 && value % 60 === 0) {
        return `${value / 60} min`;
    }

    return `${value}s`;
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

function WorkoutVideoPlayer({ url }: { url: string | null }) {
    if (!url) {
        return null;
    }

    const embedUrl = videoEmbedUrl(url);

    return (
        <div className="mt-4 overflow-hidden rounded-[1.35rem] border border-stone-200 bg-stone-950">
            <div className="flex items-center gap-2 border-b border-white/10 px-4 py-3 text-sm font-medium text-white">
                <PlayCircle className="size-4" />
                Coach video
            </div>
            {embedUrl ? (
                <iframe
                    src={embedUrl}
                    title="Workout video"
                    className="aspect-video w-full"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                />
            ) : isDirectVideo(url) ? (
                <video src={url} controls className="aspect-video w-full bg-black" />
            ) : (
                <div className="bg-white p-4">
                    <Button asChild variant="outline" size="sm">
                        <a href={url} target="_blank" rel="noreferrer">
                            Open video link
                        </a>
                    </Button>
                </div>
            )}
        </div>
    );
}

function exercisePrimaryLine(exercise: ExerciseRow) {
    if (exercise.sets && exercise.reps) {
        return `${exercise.sets} x ${exercise.reps}`;
    }

    return exercise.prescription;
}

function ExerciseCard({ exercise }: { exercise: ExerciseRow }) {
    const restLabel = formatRest(exercise.rest_seconds, exercise.rest_label);
    const primaryLine = exercisePrimaryLine(exercise);

    return (
        <div className="border-sidebar-border/70 rounded-xl border p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="font-medium">{exercise.name}</p>
                    {primaryLine && <p className="text-muted-foreground mt-2 text-sm">{primaryLine}</p>}
                </div>

                <div className="flex flex-wrap gap-2">
                    {exercise.load && <Badge variant="outline">Load {exercise.load}</Badge>}
                    {restLabel && <Badge variant="outline">Rest {restLabel}</Badge>}
                    {exercise.target && <Badge variant="outline">{exercise.target}</Badge>}
                </div>
            </div>

            {exercise.note && <p className="text-muted-foreground mt-3 text-sm leading-6">{exercise.note}</p>}
        </div>
    );
}

function ExerciseBuilder({
    id,
    value,
    onChange,
    disabled,
    hint,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    hint: string;
}) {
    const [rows, setRows] = useState<ExerciseBuilderRow[]>(() => parseExerciseBuilderRows(value));

    useEffect(() => {
        if (value.trim() === '') {
            setRows([emptyExerciseBuilderRow()]);
        }
    }, [value]);

    const updateRows = (nextRows: ExerciseBuilderRow[]) => {
        setRows(nextRows);
        onChange(serializeExerciseBuilderRows(nextRows));
    };

    const updateRow = (index: number, patch: Partial<ExerciseBuilderRow>) => {
        updateRows(rows.map((row, rowIndex) => (rowIndex === index ? { ...row, ...patch } : row)));
    };

    return (
        <div className="space-y-3">
            <div className="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
                <table className="w-full min-w-[860px] text-left text-sm">
                    <thead className="bg-stone-50 text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                        <tr>
                            <th className="px-3 py-3">Exercise</th>
                            <th className="px-3 py-3">Sets</th>
                            <th className="px-3 py-3">Reps/time</th>
                            <th className="px-3 py-3">Load</th>
                            <th className="px-3 py-3">Rest</th>
                            <th className="px-3 py-3">Target</th>
                            <th className="px-3 py-3">Note</th>
                            <th className="px-3 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={`${id}-${index}`} className="border-t border-stone-100">
                                <td className="px-3 py-3">
                                    <Input
                                        value={row.name}
                                        onChange={(event) => updateRow(index, { name: event.target.value })}
                                        placeholder="Back squat"
                                        disabled={disabled}
                                        className="h-10 min-w-44"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input
                                        type="number"
                                        min="1"
                                        value={row.sets}
                                        onChange={(event) => updateRow(index, { sets: event.target.value })}
                                        placeholder="4"
                                        disabled={disabled}
                                        className="h-10 min-w-20"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input
                                        value={row.reps}
                                        onChange={(event) => updateRow(index, { reps: event.target.value })}
                                        placeholder="6 or 40s"
                                        disabled={disabled}
                                        className="h-10 min-w-32"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input
                                        value={row.load}
                                        onChange={(event) => updateRow(index, { load: event.target.value })}
                                        placeholder="120 kg"
                                        disabled={disabled}
                                        className="h-10 min-w-32"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input
                                        value={row.rest}
                                        onChange={(event) => updateRow(index, { rest: event.target.value })}
                                        placeholder="90s"
                                        disabled={disabled}
                                        className="h-10 min-w-28"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input
                                        value={row.target}
                                        onChange={(event) => updateRow(index, { target: event.target.value })}
                                        placeholder="RPE 8"
                                        disabled={disabled}
                                        className="h-10 min-w-32"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input
                                        value={row.note}
                                        onChange={(event) => updateRow(index, { note: event.target.value })}
                                        placeholder="Technique cue"
                                        disabled={disabled}
                                        className="h-10 min-w-44"
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={disabled || rows.length === 1}
                                        onClick={() => updateRows(rows.filter((_, rowIndex) => rowIndex !== index))}
                                    >
                                        Remove
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-muted-foreground text-xs leading-5">{hint}</p>
                <Button type="button" variant="outline" size="sm" disabled={disabled} onClick={() => updateRows([...rows, emptyExerciseBuilderRow()])}>
                    Add exercise row
                </Button>
            </div>

            <details className="rounded-2xl border border-stone-200 bg-stone-50 p-3">
                <summary className="cursor-pointer text-sm font-semibold text-stone-800">Advanced paste / legacy pipe format</summary>
                <Textarea
                    id={id}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    disabled={disabled}
                    placeholder={`Back squat | 4 | 6 | 120 kg | 150s | RPE 8 | Full depth every rep\nRun intervals | 6 | 800m | Threshold pace | 90s | Hold form | Walk back easy`}
                    className="mt-3"
                />
            </details>
        </div>
    );
}

function ProgramCreateForm({ rosterAthletes, exerciseFormatHint }: { rosterAthletes: AthleteOption[]; exerciseFormatHint: string }) {
    const today = new Date().toISOString().slice(0, 10);
    const { data, setData, post, processing, errors, reset } = useForm<ProgramCreateFormData>({
        athlete_id: rosterAthletes[0] ? String(rosterAthletes[0].id) : '',
        title: '',
        goal: '',
        start_date: today,
        end_date: '',
        notes: '',
        first_session_title: '',
        first_session_date: today,
        first_session_focus: '',
        first_session_instructions: '',
        first_session_video_url: '',
        first_session_exercises: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('training.programs.store'), {
            preserveScroll: true,
            onSuccess: () =>
                reset(
                    'title',
                    'goal',
                    'end_date',
                    'notes',
                    'first_session_title',
                    'first_session_focus',
                    'first_session_instructions',
                    'first_session_video_url',
                    'first_session_exercises',
                ),
        });
    };

    return (
        <Card className="border-sidebar-border/70">
            <CardHeader>
                <CardTitle className="text-2xl">Assign a new program</CardTitle>
                <CardDescription>
                    Start with the athlete, define the block, then drop in the first session so the dashboard is useful on day one.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {rosterAthletes.length === 0 ? (
                    <div className="border-sidebar-border/70 rounded-2xl border border-dashed p-6">
                        <p className="font-medium">No active roster assignments yet.</p>
                        <p className="text-muted-foreground mt-2 text-sm leading-6">
                            Assign athletes to the coach first. Building programs for random users would be nonsense.
                        </p>
                    </div>
                ) : (
                    <form className="space-y-6" onSubmit={submit}>
                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="athlete_id">Athlete</Label>
                                <Select value={data.athlete_id} onValueChange={(value) => setData('athlete_id', value)}>
                                    <SelectTrigger id="athlete_id" disabled={processing}>
                                        <SelectValue placeholder="Select athlete" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {rosterAthletes.map((athlete) => (
                                            <SelectItem key={athlete.id} value={String(athlete.id)}>
                                                {athlete.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.athlete_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Program title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(event) => setData('title', event.target.value)}
                                    disabled={processing}
                                    placeholder="HYROX build block"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="goal">Goal</Label>
                                <Input
                                    id="goal"
                                    value={data.goal}
                                    onChange={(event) => setData('goal', event.target.value)}
                                    disabled={processing}
                                    placeholder="Raise work capacity without wrecking recovery"
                                />
                                <InputError message={errors.goal} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">Start date</Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={data.start_date}
                                        onChange={(event) => setData('start_date', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.start_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">End date</Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={data.end_date}
                                        onChange={(event) => setData('end_date', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Coach notes</Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(event) => setData('notes', event.target.value)}
                                disabled={processing}
                                placeholder="Recovery guardrails, pace targets, or anything the athlete needs in plain English."
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="border-sidebar-border/70 bg-muted/20 rounded-2xl border p-4">
                            <div className="flex items-center gap-2">
                                <Plus className="text-primary size-4" />
                                <h3 className="font-medium">First session</h3>
                            </div>

                            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_title">Session title</Label>
                                    <Input
                                        id="first_session_title"
                                        value={data.first_session_title}
                                        onChange={(event) => setData('first_session_title', event.target.value)}
                                        disabled={processing}
                                        placeholder="Tempo intervals"
                                    />
                                    <InputError message={errors.first_session_title} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_date">Session date</Label>
                                    <Input
                                        id="first_session_date"
                                        type="date"
                                        value={data.first_session_date}
                                        onChange={(event) => setData('first_session_date', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.first_session_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_focus">Focus</Label>
                                    <Input
                                        id="first_session_focus"
                                        value={data.first_session_focus}
                                        onChange={(event) => setData('first_session_focus', event.target.value)}
                                        disabled={processing}
                                        placeholder="Threshold conditioning"
                                    />
                                    <InputError message={errors.first_session_focus} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_video_url">Video URL</Label>
                                    <Input
                                        id="first_session_video_url"
                                        value={data.first_session_video_url}
                                        onChange={(event) => setData('first_session_video_url', event.target.value)}
                                        disabled={processing}
                                        placeholder="https://youtube.com/watch?v=..."
                                    />
                                    <InputError message={errors.first_session_video_url} />
                                </div>
                            </div>

                            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_instructions">Instructions</Label>
                                    <Textarea
                                        id="first_session_instructions"
                                        value={data.first_session_instructions}
                                        onChange={(event) => setData('first_session_instructions', event.target.value)}
                                        disabled={processing}
                                        placeholder="Keep the first rep smooth. If readiness is low, cap effort at 7/10."
                                    />
                                    <InputError message={errors.first_session_instructions} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_exercises">Exercise list</Label>
                                    <ExerciseBuilder
                                        id="first_session_exercises"
                                        value={data.first_session_exercises}
                                        onChange={(value) => setData('first_session_exercises', value)}
                                        disabled={processing}
                                        hint={exerciseFormatHint}
                                    />
                                    <InputError message={errors.first_session_exercises} />
                                </div>
                            </div>
                        </div>

                        <Button type="submit" disabled={processing}>
                            Create program
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function ProgramSessionForm({ programId, exerciseFormatHint }: { programId: number; exerciseFormatHint: string }) {
    const today = new Date().toISOString().slice(0, 10);
    const { data, setData, post, processing, errors, reset } = useForm<SessionCreateFormData>({
        title: '',
        scheduled_date: today,
        focus: '',
        instructions: '',
        video_url: '',
        exercises: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('training.programs.sessions.store', { trainingProgram: programId }), {
            preserveScroll: true,
            onSuccess: () => reset('title', 'focus', 'instructions', 'video_url', 'exercises'),
        });
    };

    return (
        <Card className="border-sidebar-border/70 bg-muted/20">
            <CardHeader>
                <CardTitle className="text-lg">Add session</CardTitle>
                <CardDescription>Keep the block moving. Add the next workout without leaving the page.</CardDescription>
            </CardHeader>
            <CardContent>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`session-title-${programId}`}>Session title</Label>
                            <Input
                                id={`session-title-${programId}`}
                                value={data.title}
                                onChange={(event) => setData('title', event.target.value)}
                                disabled={processing}
                                placeholder="Aerobic reset"
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`session-date-${programId}`}>Scheduled date</Label>
                            <Input
                                id={`session-date-${programId}`}
                                type="date"
                                value={data.scheduled_date}
                                onChange={(event) => setData('scheduled_date', event.target.value)}
                                disabled={processing}
                            />
                            <InputError message={errors.scheduled_date} />
                        </div>

                        <div className="grid gap-2 lg:col-span-2">
                            <Label htmlFor={`session-focus-${programId}`}>Focus</Label>
                            <Input
                                id={`session-focus-${programId}`}
                                value={data.focus}
                                onChange={(event) => setData('focus', event.target.value)}
                                disabled={processing}
                                placeholder="Low-intensity volume with clean pacing"
                            />
                            <InputError message={errors.focus} />
                        </div>

                        <div className="grid gap-2 lg:col-span-2">
                            <Label htmlFor={`session-video-${programId}`}>Video URL</Label>
                            <Input
                                id={`session-video-${programId}`}
                                value={data.video_url}
                                onChange={(event) => setData('video_url', event.target.value)}
                                disabled={processing}
                                placeholder="YouTube, Vimeo, MP4, or WebM URL"
                            />
                            <InputError message={errors.video_url} />
                        </div>
                    </div>

                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`session-instructions-${programId}`}>Instructions</Label>
                            <Textarea
                                id={`session-instructions-${programId}`}
                                value={data.instructions}
                                onChange={(event) => setData('instructions', event.target.value)}
                                disabled={processing}
                                placeholder="Stay conversational. If strain spikes, stop trying to be a hero."
                            />
                            <InputError message={errors.instructions} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`session-exercises-${programId}`}>Exercise list</Label>
                            <ExerciseBuilder
                                id={`session-exercises-${programId}`}
                                value={data.exercises}
                                onChange={(value) => setData('exercises', value)}
                                disabled={processing}
                                hint={exerciseFormatHint}
                            />
                            <InputError message={errors.exercises} />
                        </div>
                    </div>

                    <Button type="submit" variant="outline" disabled={processing}>
                        Add session
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

function WorkoutLogForm({ session, statusOptions }: { session: TrainingSessionRow; statusOptions: StatusOption[] }) {
    const today = new Date().toISOString().slice(0, 10);
    const { data, setData, post, processing, errors } = useForm<WorkoutLogFormData>({
        completion_status: session.workoutLog?.completionStatus ?? statusOptions[0]?.value ?? 'completed',
        performed_at: session.workoutLog?.performedAt ?? today,
        duration_minutes: session.workoutLog?.durationMinutes ? String(session.workoutLog.durationMinutes) : '',
        exertion_rating: session.workoutLog?.exertionRating ? String(session.workoutLog.exertionRating) : '',
        notes: session.workoutLog?.notes ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('training.sessions.log.store', { trainingSession: session.id }), {
            preserveScroll: true,
        });
    };

    return (
        <Card className="border-sidebar-border/70 bg-muted/20">
            <CardHeader>
                <CardTitle className="text-lg">{session.workoutLog ? 'Update workout log' : 'Log this workout'}</CardTitle>
                <CardDescription>Feed the coach real feedback. A plan without execution data is just decoration.</CardDescription>
            </CardHeader>
            <CardContent>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`completion-status-${session.id}`}>Status</Label>
                            <Select value={data.completion_status} onValueChange={(value) => setData('completion_status', value)}>
                                <SelectTrigger id={`completion-status-${session.id}`} disabled={processing}>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statusOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.completion_status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`performed-at-${session.id}`}>Performed on</Label>
                            <Input
                                id={`performed-at-${session.id}`}
                                type="date"
                                value={data.performed_at}
                                onChange={(event) => setData('performed_at', event.target.value)}
                                disabled={processing}
                            />
                            <InputError message={errors.performed_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`duration-${session.id}`}>Duration (minutes)</Label>
                            <Input
                                id={`duration-${session.id}`}
                                type="number"
                                min="1"
                                max="600"
                                value={data.duration_minutes}
                                onChange={(event) => setData('duration_minutes', event.target.value)}
                                disabled={processing}
                                placeholder="45"
                            />
                            <InputError message={errors.duration_minutes} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`rpe-${session.id}`}>Exertion (1-10)</Label>
                            <Input
                                id={`rpe-${session.id}`}
                                type="number"
                                min="1"
                                max="10"
                                value={data.exertion_rating}
                                onChange={(event) => setData('exertion_rating', event.target.value)}
                                disabled={processing}
                                placeholder="7"
                            />
                            <InputError message={errors.exertion_rating} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`workout-notes-${session.id}`}>Notes</Label>
                        <Textarea
                            id={`workout-notes-${session.id}`}
                            value={data.notes}
                            onChange={(event) => setData('notes', event.target.value)}
                            disabled={processing}
                            placeholder="Legs were flat for the first two rounds, then settled in."
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {session.workoutLog ? 'Update log' : 'Submit log'}
                    </Button>
                </form>
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

function AthleteTrainingExperience({ summary, programs, statusOptions }: Pick<TrainingPageProps, 'summary' | 'programs' | 'statusOptions'>) {
    const primaryProgram = programs.data.find((program) => ['active', 'draft'].includes(program.status)) ?? programs.data[0] ?? null;
    const nextSession = primaryProgram?.sessions.find((session) => {
        if (!session.scheduledDate) {
            return true;
        }

        return session.scheduledDate >= new Date().toISOString().slice(0, 10);
    });
    const completedLogs = programs.data
        .flatMap((program) => program.sessions)
        .filter((session) => session.workoutLog?.completionStatus === 'completed').length;
    const loggedSessions = programs.data.flatMap((program) => program.sessions).filter((session) => session.workoutLog).length;
    const compliance = loggedSessions === 0 ? 'No logs yet' : `${Math.round((completedLogs / loggedSessions) * 100)}%`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Training" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <AthleteHero
                    eyebrow="Athlete training board"
                    title={primaryProgram ? primaryProgram.title : 'Training is ready when your coach assigns the block.'}
                    description={
                        primaryProgram
                            ? `${primaryProgram.coachName} owns the programming. You own the execution, the log, and whether the data stays honest.`
                            : 'Once a coach assigns a block, this becomes the athlete-facing source of truth for sessions, exercise detail, and log submission.'
                    }
                    badges={
                        primaryProgram
                            ? [humanizeStatus(primaryProgram.status), `${primaryProgram.sessionCount} sessions`, `Coach ${primaryProgram.coachName}`]
                            : ['Awaiting program']
                    }
                    actions={
                        <>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/dashboard">Back to dashboard</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/wearables">Open recovery</Link>
                            </Button>
                        </>
                    }
                >
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                            <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Next session</p>
                            <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{nextSession?.title ?? 'Not scheduled'}</p>
                            <p className="mt-2 text-sm text-stone-600">
                                {nextSession?.scheduledDate ? shortDayLabel(nextSession.scheduledDate) : 'Coach still needs to set the date.'}
                            </p>
                        </div>
                        <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                            <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Program focus</p>
                            <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{primaryProgram?.goal ?? 'No focus set yet'}</p>
                            <p className="mt-2 text-sm text-stone-600">{primaryProgram?.notes ?? 'Coach note will land here once it exists.'}</p>
                        </div>
                    </div>
                </AthleteHero>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <AthleteMetricCard
                        title="Tracked programs"
                        value={summary.trackedPrograms.toString()}
                        note="Visible training blocks in your account."
                        icon={Dumbbell}
                    />
                    <AthleteMetricCard
                        title="Scheduled this week"
                        value={summary.scheduledThisWeek.toString()}
                        note="Sessions lined up over the next seven days."
                        icon={CalendarDays}
                        tone="amber"
                    />
                    <AthleteMetricCard
                        title="Completed this week"
                        value={String(summary.completedThisWeek ?? 0)}
                        note="Logged sessions marked completed this week."
                        icon={CheckCircle2}
                    />
                    <AthleteMetricCard
                        title="Log compliance"
                        value={compliance}
                        note="Completion against your recent submitted logs."
                        icon={TrendingUp}
                        tone="stone"
                    />
                </section>

                <section className="space-y-4">
                    <AthleteSectionHeading
                        eyebrow="Program stack"
                        title="Read the plan fast, then go do the damn work."
                        description="Every session keeps the exercise prescription, coach instruction, and athlete log in one place so the plan does not fragment."
                    />
                    {programs.data.length === 0 ? (
                        <AthletePanel
                            title="No programs yet"
                            description="That is either an empty roster assignment or a coach who still owes you actual programming."
                            contentClassName="p-0"
                        >
                            <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-6 text-sm leading-6 text-stone-500">
                                No training programs are visible yet.
                            </div>
                        </AthletePanel>
                    ) : (
                        <div className="space-y-4">
                            {programs.data.map((program) => (
                                <AthletePanel
                                    key={program.id}
                                    title={program.title}
                                    description={`${program.coachName} · ${program.goal ?? 'No written goal yet.'}`}
                                    contentClassName="space-y-5"
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant={badgeVariantForProgram(program.status)}>{humanizeStatus(program.status)}</Badge>
                                            <Badge variant="outline">{program.sessionCount} sessions</Badge>
                                            <Badge variant="outline">
                                                Next {program.nextSessionDate ? shortDayLabel(program.nextSessionDate) : 'not scheduled'}
                                            </Badge>
                                        </div>
                                        <div className="grid gap-3 sm:grid-cols-3 lg:w-[28rem]">
                                            <div className="rounded-xl border border-stone-200/75 bg-stone-50/80 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Start</p>
                                                <p className="mt-2 text-sm font-medium text-stone-950">{program.startDate ?? 'Not set'}</p>
                                            </div>
                                            <div className="rounded-xl border border-stone-200/75 bg-stone-50/80 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">End</p>
                                                <p className="mt-2 text-sm font-medium text-stone-950">{program.endDate ?? 'Open end'}</p>
                                            </div>
                                            <div className="rounded-xl border border-stone-200/75 bg-stone-50/80 p-3">
                                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Logs</p>
                                                <p className="mt-2 text-sm font-medium text-stone-950">
                                                    {program.sessions.filter((session) => session.workoutLog).length}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {program.notes && (
                                        <div className="rounded-[1.5rem] border border-stone-200/75 bg-[linear-gradient(135deg,rgba(255,247,237,0.8),rgba(240,253,250,0.7))] p-4">
                                            <div className="flex items-center gap-2">
                                                <NotebookText className="size-4 text-stone-700" />
                                                <p className="text-sm font-medium text-stone-900">Coach note</p>
                                            </div>
                                            <p className="mt-3 text-sm leading-6 text-stone-600">{program.notes}</p>
                                        </div>
                                    )}

                                    <div className="space-y-3">
                                        {program.sessions.length === 0 ? (
                                            <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-5 text-sm leading-6 text-stone-500">
                                                No sessions have been added to this program yet.
                                            </div>
                                        ) : (
                                            program.sessions.map((session) => (
                                                <div
                                                    key={session.id}
                                                    className="rounded-[1.65rem] border border-stone-200/80 bg-white/90 p-5 shadow-sm"
                                                >
                                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                                        <div className="space-y-2">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <p className="text-lg font-semibold tracking-tight text-stone-950">{session.title}</p>
                                                                {session.scheduledDate && (
                                                                    <Badge variant="outline">{shortDayLabel(session.scheduledDate)}</Badge>
                                                                )}
                                                                {session.focus && <Badge variant="outline">{session.focus}</Badge>}
                                                            </div>
                                                            {session.instructions && (
                                                                <p className="max-w-3xl text-sm leading-6 text-stone-600">{session.instructions}</p>
                                                            )}
                                                            <WorkoutVideoPlayer url={session.videoUrl} />
                                                        </div>

                                                        {session.workoutLog ? (
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <Badge variant={badgeVariantForWorkout(session.workoutLog.completionStatus)}>
                                                                    {humanizeStatus(session.workoutLog.completionStatus)}
                                                                </Badge>
                                                                {session.workoutLog.durationMinutes && (
                                                                    <Badge variant="outline">{session.workoutLog.durationMinutes} min</Badge>
                                                                )}
                                                                {session.workoutLog.exertionRating && (
                                                                    <Badge variant="outline">RPE {session.workoutLog.exertionRating}</Badge>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div className="flex items-center gap-2 rounded-full border border-stone-200 bg-stone-50 px-4 py-2 text-sm text-stone-600">
                                                                <Clock3 className="size-4" />
                                                                Log pending
                                                            </div>
                                                        )}
                                                    </div>

                                                    {session.exercises.length > 0 && (
                                                        <div className="mt-4 grid gap-3 lg:grid-cols-2">
                                                            {session.exercises.map((exercise, index) => (
                                                                <ExerciseCard key={`${session.id}-${index}`} exercise={exercise} />
                                                            ))}
                                                        </div>
                                                    )}

                                                    <div className="mt-4">
                                                        <WorkoutLogForm session={session} statusOptions={statusOptions} />
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </AthletePanel>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}

export default function TrainingIndex({
    viewerRole,
    scopeLabel,
    summary,
    programs,
    rosterAthletes,
    canCreatePrograms,
    statusOptions,
    exerciseFormatHint,
}: TrainingPageProps) {
    if (viewerRole === 'athlete') {
        return <AthleteTrainingExperience summary={summary} programs={programs} statusOptions={statusOptions} />;
    }

    const cards = metricCards(viewerRole, summary);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Training" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <WorkspaceHero
                    eyebrow={viewerRole === 'coach' ? 'Coach training control' : 'Training oversight'}
                    title={
                        viewerRole === 'coach'
                            ? 'Programming should be easy to assign and easier to review.'
                            : 'Training visibility should stay readable even at platform scope.'
                    }
                    description={`${scopeLabel} The point of this page is to keep block design, session detail, and athlete execution in one clean flow instead of scattering them across random notes and side chats.`}
                    badges={[`${summary.activePrograms} active blocks`, `${summary.scheduledThisWeek} scheduled this week`]}
                    actions={
                        <>
                            {canCreatePrograms && (
                                <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                    <a href="#program-composer">
                                        <Plus className="mr-2 size-4" />
                                        Build a program
                                    </a>
                                </Button>
                            )}
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/roster">
                                    <ClipboardList className="mr-2 size-4" />
                                    Open roster
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/progress">
                                    <TrendingUp className="mr-2 size-4" />
                                    Open progress
                                </Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Why this matters</p>
                                <p className="mt-3 text-lg font-semibold tracking-tight text-stone-950">{roleCallout(viewerRole)}</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Programs in view</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{programs.total}</p>
                                <p className="mt-2 text-sm text-stone-600">Visible training records in the current scope.</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Exercise format</p>
                                <p className="mt-3 text-sm leading-6 text-stone-600">{exerciseFormatHint}</p>
                                <p className="mt-2 text-xs leading-5 text-stone-500">
                                    Example: `Exercise | sets | reps or time | load | rest | target | note`
                                </p>
                            </div>
                        </div>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {cards.map((card) => (
                        <WorkspaceMetricCard key={card.title} title={card.title} value={card.value} note={card.note} icon={card.icon} />
                    ))}
                </div>

                {canCreatePrograms && (
                    <section id="program-composer" className="space-y-4">
                        <WorkspaceSectionHeading
                            eyebrow="Program composer"
                            title="Assign the block once, then let the athlete execute against something clear."
                            description="Start with the athlete, define the goal, and seed the first session so the training workspace is useful immediately instead of after three more admin steps."
                        />
                        <ProgramCreateForm rosterAthletes={rosterAthletes} exerciseFormatHint={exerciseFormatHint} />
                    </section>
                )}

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Program library"
                        title="Every block, session, and athlete log in one readable stack."
                        description={
                            viewerRole === 'coach'
                                ? 'Your visible programming blocks, upcoming sessions, and athlete feedback should read like a coaching workflow, not a database dump.'
                                : 'Platform-wide training visibility for support and operations without making you drown in detail.'
                        }
                    />

                    <WorkspacePanel
                        title="Programs in view"
                        description={`${programs.total} program record(s) in the current view.`}
                        contentClassName="space-y-4"
                    >
                        <WorkspaceTable minWidth="min-w-[1180px]">
                            <WorkspaceTableHeader labels={['Program', 'Athlete', 'Coach', 'Status', 'Dates', 'Sessions', 'Next', 'Notes']} />
                            {programs.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No training programs yet." colSpan={8} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {programs.data.map((program) => (
                                        <Fragment key={program.id}>
                                            <tr key={`program-${program.id}`} className="align-top transition-colors hover:bg-stone-50/80">
                                                <td className="px-4 py-4">
                                                    <p className="font-semibold text-stone-950">{program.title}</p>
                                                    {program.goal && <p className="mt-1 text-xs text-stone-500">{program.goal}</p>}
                                                </td>
                                                <td className="px-4 py-4 font-medium text-stone-950">{program.athleteName}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{program.coachName}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={badgeVariantForProgram(program.status)}>{humanizeStatus(program.status)}</Badge>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">
                                                    {program.startDate ?? 'Not set'} → {program.endDate ?? 'Open ended'}
                                                </td>
                                                <td className="px-4 py-4 font-medium text-stone-950">{program.sessionCount}</td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{program.nextSessionDate ?? 'Not scheduled'}</td>
                                                <td className="px-4 py-4">
                                                    <p className="line-clamp-2 max-w-[18rem] text-sm text-stone-700">
                                                        {program.notes ?? 'No coach notes.'}
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr key={`program-detail-${program.id}`} className="bg-stone-50/50">
                                                <td colSpan={8} className="px-4 py-4">
                                                    {viewerRole === 'coach' && (
                                                        <div className="mb-4">
                                                            <ProgramSessionForm programId={program.id} exerciseFormatHint={exerciseFormatHint} />
                                                        </div>
                                                    )}

                                                    <div className="space-y-3">
                                                        <div className="flex items-center gap-2 text-sm font-semibold text-stone-950">
                                                            <Timer className="size-4" />
                                                            Sessions and exercise prescription
                                                        </div>

                                                        <WorkspaceTable minWidth="min-w-[1040px]">
                                                            <WorkspaceTableHeader
                                                                labels={['Session', 'Scheduled', 'Focus', 'Video', 'Workout log', 'Exercises']}
                                                            />
                                                            {program.sessions.length === 0 ? (
                                                                <WorkspaceTableEmpty message="No sessions have been added to this program yet." colSpan={6} />
                                                            ) : (
                                                                <tbody className="divide-y divide-stone-100">
                                                                    {program.sessions.map((session) => (
                                                                        <tr key={session.id} className="align-top">
                                                                            <td className="px-4 py-4">
                                                                                <p className="font-medium text-stone-950">{session.title}</p>
                                                                                <p className="mt-1 max-w-[18rem] text-xs leading-5 text-stone-500">
                                                                                    {session.instructions ?? 'No instructions.'}
                                                                                </p>
                                                                                {viewerRole === 'athlete' && (
                                                                                    <div className="mt-3">
                                                                                        <WorkoutLogForm session={session} statusOptions={statusOptions} />
                                                                                    </div>
                                                                                )}
                                                                            </td>
                                                                            <td className="px-4 py-4 text-sm text-stone-700">
                                                                                {session.scheduledDate ?? 'Not scheduled'}
                                                                            </td>
                                                                            <td className="px-4 py-4 text-sm text-stone-700">{session.focus ?? 'General'}</td>
                                                                            <td className="px-4 py-4">
                                                                                <WorkoutVideoPlayer url={session.videoUrl} />
                                                                            </td>
                                                                            <td className="px-4 py-4">
                                                                                {session.workoutLog ? (
                                                                                    <div className="space-y-1 text-xs text-stone-600">
                                                                                        <Badge
                                                                                            variant={badgeVariantForWorkout(
                                                                                                session.workoutLog.completionStatus,
                                                                                            )}
                                                                                        >
                                                                                            {humanizeStatus(session.workoutLog.completionStatus)}
                                                                                        </Badge>
                                                                                        <p>{session.workoutLog.performedAt ?? 'Not dated'}</p>
                                                                                        <p>
                                                                                            {session.workoutLog.durationMinutes
                                                                                                ? `${session.workoutLog.durationMinutes} min`
                                                                                                : 'No duration'}{' '}
                                                                                            ·{' '}
                                                                                            {session.workoutLog.exertionRating
                                                                                                ? `RPE ${session.workoutLog.exertionRating}`
                                                                                                : 'No RPE'}
                                                                                        </p>
                                                                                        {session.workoutLog.notes && (
                                                                                            <p className="line-clamp-2 max-w-[14rem]">
                                                                                                {session.workoutLog.notes}
                                                                                            </p>
                                                                                        )}
                                                                                    </div>
                                                                                ) : (
                                                                                    <Badge variant="outline">No workout log yet</Badge>
                                                                                )}
                                                                            </td>
                                                                            <td className="px-4 py-4">
                                                                                <WorkspaceTable minWidth="min-w-[720px]">
                                                                                    <WorkspaceTableHeader
                                                                                        labels={['Exercise', 'Sets', 'Reps/time', 'Load', 'Rest', 'Target']}
                                                                                    />
                                                                                    {session.exercises.length === 0 ? (
                                                                                        <WorkspaceTableEmpty message="No exercises listed." colSpan={6} />
                                                                                    ) : (
                                                                                        <tbody className="divide-y divide-stone-100">
                                                                                            {session.exercises.map((exercise, index) => (
                                                                                                <tr key={`${session.id}-${index}`}>
                                                                                                    <td className="px-4 py-3">
                                                                                                        <p className="font-medium text-stone-950">
                                                                                                            {exercise.name}
                                                                                                        </p>
                                                                                                        <p className="mt-1 line-clamp-2 max-w-[12rem] text-xs text-stone-500">
                                                                                                            {exercise.note ??
                                                                                                                exercise.prescription ??
                                                                                                                'No note.'}
                                                                                                        </p>
                                                                                                    </td>
                                                                                                    <td className="px-4 py-3 text-sm text-stone-700">
                                                                                                        {exercise.sets ?? '-'}
                                                                                                    </td>
                                                                                                    <td className="px-4 py-3 text-sm text-stone-700">
                                                                                                        {exercise.reps ?? '-'}
                                                                                                    </td>
                                                                                                    <td className="px-4 py-3 text-sm text-stone-700">
                                                                                                        {exercise.load ?? '-'}
                                                                                                    </td>
                                                                                                    <td className="px-4 py-3 text-sm text-stone-700">
                                                                                                        {exercise.rest_label ?? '-'}
                                                                                                    </td>
                                                                                                    <td className="px-4 py-3 text-sm text-stone-700">
                                                                                                        {exercise.target ?? '-'}
                                                                                                    </td>
                                                                                                </tr>
                                                                                            ))}
                                                                                        </tbody>
                                                                                    )}
                                                                                </WorkspaceTable>
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            )}
                                                        </WorkspaceTable>
                                                    </div>
                                                </td>
                                            </tr>
                                        </Fragment>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <div className="flex items-center justify-between">
                        <Button variant="outline" asChild disabled={!programs.prev_page_url}>
                            {programs.prev_page_url ? (
                                <Link href={programs.prev_page_url} preserveScroll>
                                    <ArrowLeft className="mr-2 size-4" />
                                    Previous
                                </Link>
                            ) : (
                                <span>
                                    <ArrowLeft className="mr-2 size-4" />
                                    Previous
                                </span>
                            )}
                        </Button>

                        <p className="text-muted-foreground text-sm">
                            Page {programs.current_page} of {programs.last_page}
                        </p>

                        <Button variant="outline" asChild disabled={!programs.next_page_url}>
                            {programs.next_page_url ? (
                                <Link href={programs.next_page_url} preserveScroll>
                                    Next
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            ) : (
                                <span>
                                    Next
                                    <ArrowRight className="ml-2 size-4" />
                                </span>
                            )}
                        </Button>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
