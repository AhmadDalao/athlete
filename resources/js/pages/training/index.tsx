import { AthleteHero, AthleteMetricCard, AthletePanel, AthleteSectionHeading } from '@/components/athlete-page-primitives';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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
    type LucideIcon,
    NotebookText,
    Plus,
    Timer,
    TrendingUp,
} from 'lucide-react';
import { type FormEvent, type TextareaHTMLAttributes } from 'react';

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
    first_session_exercises: string;
}

interface SessionCreateFormData {
    title: string;
    scheduled_date: string;
    focus: string;
    instructions: string;
    exercises: string;
}

interface WorkoutLogFormData {
    completion_status: string;
    performed_at: string;
    duration_minutes: string;
    exertion_rating: string;
    notes: string;
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

function TextArea({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            className={cn(
                'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-24 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
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
                            <TextArea
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
                            </div>

                            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_session_instructions">Instructions</Label>
                                    <TextArea
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
                                    <TextArea
                                        id="first_session_exercises"
                                        value={data.first_session_exercises}
                                        onChange={(event) => setData('first_session_exercises', event.target.value)}
                                        disabled={processing}
                                        placeholder={`Back squat | 4 | 6 | 120 kg | 150s | RPE 8 | Full depth every rep\nRun intervals | 6 | 800m | Threshold pace | 90s | Hold form | Walk back easy`}
                                    />
                                    <p className="text-muted-foreground text-xs leading-5">{exerciseFormatHint}</p>
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
        exercises: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('training.programs.sessions.store', { trainingProgram: programId }), {
            preserveScroll: true,
            onSuccess: () => reset('title', 'focus', 'instructions', 'exercises'),
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
                    </div>

                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`session-instructions-${programId}`}>Instructions</Label>
                            <TextArea
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
                            <TextArea
                                id={`session-exercises-${programId}`}
                                value={data.exercises}
                                onChange={(event) => setData('exercises', event.target.value)}
                                disabled={processing}
                                placeholder={`Trap-bar deadlift | 5 | 3 | 140 kg | 180s | Fast concentric | Reset each rep\nBike flush | 1 | 20 min zone 2 | Easy spin | 0s | Nasal breathing | Keep cadence smooth`}
                            />
                            <p className="text-muted-foreground text-xs leading-5">{exerciseFormatHint}</p>
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
                        <TextArea
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

            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl bg-[linear-gradient(180deg,rgba(248,250,252,0.96),rgba(255,255,255,1))] p-4 md:p-6">
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

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Card className="border-sidebar-border/70 from-background via-background to-muted/40 bg-linear-to-br">
                    <CardHeader>
                        <CardTitle className="text-3xl">Training workspace</CardTitle>
                        <CardDescription className="max-w-3xl leading-6">
                            {scopeLabel}. This is where training intent, session execution, and coach visibility finally live in the same place.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 lg:grid-cols-[1.4fr_0.6fr]">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            {cards.map((card) => (
                                <MetricCard key={card.title} title={card.title} value={card.value} note={card.note} icon={card.icon} />
                            ))}
                        </div>

                        <div className="border-sidebar-border/70 bg-muted/30 rounded-2xl border p-5">
                            <p className="text-muted-foreground text-sm font-medium">Why this matters</p>
                            <p className="mt-3 text-lg font-semibold tracking-tight">{roleCallout(viewerRole)}</p>
                            <div className="border-sidebar-border/70 bg-background/80 mt-4 rounded-xl border border-dashed p-3">
                                <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Exercise format</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">{exerciseFormatHint}</p>
                                <p className="text-muted-foreground mt-2 text-xs leading-5">
                                    Example: `Exercise | sets | reps or time | load | rest | target | note`
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {canCreatePrograms && <ProgramCreateForm rosterAthletes={rosterAthletes} exerciseFormatHint={exerciseFormatHint} />}

                <section className="space-y-4">
                    <div className="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 className="text-xl font-semibold tracking-tight">Programs</h2>
                            <p className="text-muted-foreground text-sm leading-6">
                                {viewerRole === 'coach'
                                    ? 'Your roster programming blocks, upcoming sessions, and athlete feedback.'
                                    : viewerRole === 'athlete'
                                      ? 'Your assigned programming and session-level log history.'
                                      : 'Platform-wide training visibility for support and operations.'}
                            </p>
                        </div>
                        <p className="text-muted-foreground text-sm">{programs.total} program record(s) in the current view.</p>
                    </div>

                    <Card className="border-sidebar-border/70">
                        <CardContent className="space-y-4 p-4">
                            {programs.data.length === 0 ? (
                                <div className="border-sidebar-border/70 rounded-xl border border-dashed p-8 text-center">
                                    <p className="font-medium">No training programs yet.</p>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        That is either an empty roster or a product that still needs to earn its name.
                                    </p>
                                </div>
                            ) : (
                                programs.data.map((program) => (
                                    <div key={program.id} className="border-sidebar-border/70 rounded-2xl border p-4">
                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div className="space-y-2">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-xl font-semibold tracking-tight">{program.title}</p>
                                                    <Badge variant={badgeVariantForProgram(program.status)}>{humanizeStatus(program.status)}</Badge>
                                                </div>
                                                <p className="text-muted-foreground text-sm">
                                                    Coach: {program.coachName} · Athlete: {program.athleteName}
                                                </p>
                                                <div className="flex flex-wrap gap-2">
                                                    {program.goal && <Badge variant="outline">{program.goal}</Badge>}
                                                    <Badge variant="outline">{program.sessionCount} session(s)</Badge>
                                                    <Badge variant="outline">Next: {program.nextSessionDate ?? 'Not scheduled'}</Badge>
                                                </div>
                                            </div>

                                            <div className="grid gap-3 sm:grid-cols-3 xl:w-[28rem]">
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Start</p>
                                                    <p className="mt-2 text-sm font-medium">{program.startDate ?? 'Not set'}</p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">End</p>
                                                    <p className="mt-2 text-sm font-medium">{program.endDate ?? 'Open ended'}</p>
                                                </div>
                                                <div className="border-sidebar-border/70 rounded-xl border p-3">
                                                    <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Sessions</p>
                                                    <p className="mt-2 text-sm font-medium">{program.sessionCount}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {program.notes && (
                                            <div className="border-sidebar-border/70 bg-muted/20 mt-4 rounded-xl border p-4">
                                                <div className="flex items-center gap-2">
                                                    <NotebookText className="text-primary size-4" />
                                                    <p className="text-sm font-medium">Coach note</p>
                                                </div>
                                                <p className="text-muted-foreground mt-2 text-sm leading-6">{program.notes}</p>
                                            </div>
                                        )}

                                        {viewerRole === 'coach' && (
                                            <div className="mt-4">
                                                <ProgramSessionForm programId={program.id} exerciseFormatHint={exerciseFormatHint} />
                                            </div>
                                        )}

                                        <div className="mt-4 space-y-3">
                                            <div className="flex items-center gap-2">
                                                <Timer className="text-primary size-4" />
                                                <h3 className="font-medium">Sessions</h3>
                                            </div>

                                            {program.sessions.length === 0 ? (
                                                <div className="border-sidebar-border/70 rounded-xl border border-dashed p-4">
                                                    <p className="text-muted-foreground text-sm">No sessions have been added to this program yet.</p>
                                                </div>
                                            ) : (
                                                program.sessions.map((session) => (
                                                    <div
                                                        key={session.id}
                                                        className="border-sidebar-border/70 bg-background/70 rounded-2xl border p-4"
                                                    >
                                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                                            <div className="space-y-2">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <p className="font-medium">{session.title}</p>
                                                                    {session.scheduledDate && (
                                                                        <Badge variant="outline">{session.scheduledDate}</Badge>
                                                                    )}
                                                                    {session.focus && <Badge variant="outline">{session.focus}</Badge>}
                                                                </div>
                                                                {session.instructions && (
                                                                    <p className="text-muted-foreground max-w-3xl text-sm leading-6">
                                                                        {session.instructions}
                                                                    </p>
                                                                )}
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
                                                                <Badge variant="outline">No workout log yet</Badge>
                                                            )}
                                                        </div>

                                                        {session.exercises.length > 0 && (
                                                            <div className="mt-4 grid gap-3 lg:grid-cols-2">
                                                                {session.exercises.map((exercise, index) => (
                                                                    <ExerciseCard key={`${session.id}-${index}`} exercise={exercise} />
                                                                ))}
                                                            </div>
                                                        )}

                                                        {viewerRole === 'athlete' ? (
                                                            <div className="mt-4">
                                                                <WorkoutLogForm session={session} statusOptions={statusOptions} />
                                                            </div>
                                                        ) : session.workoutLog ? (
                                                            <div className="border-sidebar-border/70 bg-muted/20 mt-4 rounded-xl border p-4">
                                                                <p className="text-sm font-medium">Athlete log</p>
                                                                <div className="mt-3 grid gap-3 md:grid-cols-3">
                                                                    <div>
                                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                            Completed on
                                                                        </p>
                                                                        <p className="mt-2 text-sm font-medium">
                                                                            {session.workoutLog.performedAt ?? 'Not logged'}
                                                                        </p>
                                                                    </div>
                                                                    <div>
                                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                            Duration
                                                                        </p>
                                                                        <p className="mt-2 text-sm font-medium">
                                                                            {session.workoutLog.durationMinutes
                                                                                ? `${session.workoutLog.durationMinutes} min`
                                                                                : 'Not logged'}
                                                                        </p>
                                                                    </div>
                                                                    <div>
                                                                        <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">
                                                                            Exertion
                                                                        </p>
                                                                        <p className="mt-2 text-sm font-medium">
                                                                            {session.workoutLog.exertionRating
                                                                                ? `RPE ${session.workoutLog.exertionRating}`
                                                                                : 'Not logged'}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                {session.workoutLog.notes && (
                                                                    <p className="text-muted-foreground mt-3 text-sm leading-6">
                                                                        {session.workoutLog.notes}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div className="border-sidebar-border/70 mt-4 rounded-xl border border-dashed p-4">
                                                                <p className="text-muted-foreground text-sm">
                                                                    Athlete feedback has not been submitted for this session yet.
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

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
