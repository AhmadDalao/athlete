import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Activity, Archive, ArrowLeft, ArrowRight, ClipboardList, Dumbbell, PauseCircle, Shield, UserRoundPlus, Users, Watch } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roster',
        href: '/roster',
    },
];

interface Option {
    value: string;
    label: string;
}

interface AssignmentRow {
    id: number;
    status: string;
    goal: string | null;
    notes: string | null;
    startedAt: string | null;
    endedAt: string | null;
    coach: {
        id: number;
        name: string;
        email: string;
    };
    athlete: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        primaryGoal: string | null;
        preferredContactMethod: string | null;
    };
    membership: {
        status: string;
        planName: string;
        daysRemaining: number | null;
    } | null;
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
    } | null;
}

interface AssignmentPaginator {
    data: AssignmentRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface RosterPageProps {
    viewerRole: string | null;
    scopeLabel: string;
    summary: {
        totalAssignments: number;
        activeAssignments: number;
        pausedAssignments: number;
        archivedAssignments: number;
        activeAthletes: number;
        availableAthletes: number;
        athletesMissingRecentCheckIns: number;
        representedCoaches: number;
    };
    assignments: AssignmentPaginator;
    coachOptions: Option[];
    athleteOptions: Option[];
    statusOptions: Option[];
}

interface AssignmentFormData {
    coach_id: string;
    athlete_id: string;
    status: string;
    goal: string;
    notes: string;
    started_at: string;
    ended_at: string;
}

interface AssignmentUpdateFormData {
    status: string;
    goal: string;
    notes: string;
    started_at: string;
    ended_at: string;
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
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
    return score === null ? 'No readiness yet' : `Readiness ${score}`;
}

function formatSleepHours(hours: number | null) {
    return hours === null ? 'No sleep data' : `${hours.toFixed(1)}h sleep`;
}

function badgeVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['archived', 'cancelled', 'expired', 'past_due'].includes(status)) {
        return 'destructive';
    }

    if (['paused', 'grace'].includes(status)) {
        return 'secondary';
    }

    if (['active', 'trialing'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

function todayDate() {
    return new Date().toISOString().slice(0, 10);
}

function MetricCard({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: typeof Users }) {
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

function CreateAssignmentDialog({
    viewerRole,
    coachOptions,
    athleteOptions,
    statusOptions,
}: Pick<RosterPageProps, 'viewerRole' | 'coachOptions' | 'athleteOptions' | 'statusOptions'>) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<AssignmentFormData>({
        coach_id: coachOptions[0]?.value ?? '',
        athlete_id: athleteOptions[0]?.value ?? '',
        status: 'active',
        goal: '',
        notes: '',
        started_at: todayDate(),
        ended_at: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('roster.assignments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset('athlete_id', 'goal', 'notes', 'ended_at');
                setData('status', 'active');
                setData('started_at', todayDate());
            },
        });
    };

    const disabled = athleteOptions.length === 0 || (viewerRole === 'admin' && coachOptions.length === 0);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button disabled={disabled}>
                    <UserRoundPlus className="mr-2 size-4" />
                    Add assignment
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create roster assignment</DialogTitle>
                    <DialogDescription>
                        Assign an athlete cleanly, add the working goal, and stop letting roster control live in the shadows.
                    </DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        {viewerRole === 'admin' && (
                            <div className="grid gap-2">
                                <Label htmlFor="assignment-coach">Coach</Label>
                                <Select value={data.coach_id} onValueChange={(value) => setData('coach_id', value)}>
                                    <SelectTrigger id="assignment-coach">
                                        <SelectValue placeholder="Choose coach" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {coachOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.coach_id} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="assignment-athlete">Athlete</Label>
                            <Select value={data.athlete_id} onValueChange={(value) => setData('athlete_id', value)}>
                                <SelectTrigger id="assignment-athlete">
                                    <SelectValue placeholder="Choose athlete" />
                                </SelectTrigger>
                                <SelectContent>
                                    {athleteOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.athlete_id} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="assignment-status">Status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger id="assignment-status">
                                    <SelectValue placeholder="Choose status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statusOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="assignment-started-at">Started at</Label>
                            <Input
                                id="assignment-started-at"
                                type="date"
                                value={data.started_at}
                                onChange={(event) => setData('started_at', event.target.value)}
                            />
                            <InputError message={errors.started_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="assignment-ended-at">Ended at</Label>
                            <Input
                                id="assignment-ended-at"
                                type="date"
                                value={data.ended_at}
                                onChange={(event) => setData('ended_at', event.target.value)}
                            />
                            <InputError message={errors.ended_at} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="assignment-goal">Coaching goal</Label>
                        <Input
                            id="assignment-goal"
                            value={data.goal}
                            onChange={(event) => setData('goal', event.target.value)}
                            placeholder="What is the point of this assignment?"
                        />
                        <InputError message={errors.goal} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="assignment-notes">Notes</Label>
                        <Textarea
                            id="assignment-notes"
                            value={data.notes}
                            onChange={(event) => setData('notes', event.target.value)}
                            placeholder="Anything the next coach or admin will thank you for documenting?"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing || disabled}>
                            Save assignment
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditAssignmentDialog({ assignment, statusOptions }: { assignment: AssignmentRow; statusOptions: Option[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm<AssignmentUpdateFormData>({
        status: assignment.status,
        goal: assignment.goal ?? '',
        notes: assignment.notes ?? '',
        started_at: assignment.startedAt ?? '',
        ended_at: assignment.endedAt ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(route('roster.assignments.update', assignment.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Edit assignment
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit {assignment.athlete.name}</DialogTitle>
                    <DialogDescription>Update status, context, and timing without creating roster chaos.</DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor={`assignment-status-${assignment.id}`}>Status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger id={`assignment-status-${assignment.id}`}>
                                    <SelectValue placeholder="Choose status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statusOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`assignment-started-at-${assignment.id}`}>Started at</Label>
                            <Input
                                id={`assignment-started-at-${assignment.id}`}
                                type="date"
                                value={data.started_at}
                                onChange={(event) => setData('started_at', event.target.value)}
                            />
                            <InputError message={errors.started_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`assignment-ended-at-${assignment.id}`}>Ended at</Label>
                            <Input
                                id={`assignment-ended-at-${assignment.id}`}
                                type="date"
                                value={data.ended_at}
                                onChange={(event) => setData('ended_at', event.target.value)}
                            />
                            <InputError message={errors.ended_at} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`assignment-goal-${assignment.id}`}>Coaching goal</Label>
                        <Input
                            id={`assignment-goal-${assignment.id}`}
                            value={data.goal}
                            onChange={(event) => setData('goal', event.target.value)}
                            placeholder="What is this athlete block trying to accomplish?"
                        />
                        <InputError message={errors.goal} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`assignment-notes-${assignment.id}`}>Notes</Label>
                        <Textarea
                            id={`assignment-notes-${assignment.id}`}
                            value={data.notes}
                            onChange={(event) => setData('notes', event.target.value)}
                            placeholder="Context, restrictions, communication notes, or anything else worth not forgetting."
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RosterIndex({ viewerRole, scopeLabel, summary, assignments, coachOptions, athleteOptions, statusOptions }: RosterPageProps) {
    const isAdmin = viewerRole === 'admin';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roster" />

            <div className="space-y-6 px-4 py-6 md:px-6">
                <Card className="border-sidebar-border/70 overflow-hidden">
                    <CardHeader className="from-primary/10 via-background to-background relative overflow-hidden bg-linear-to-br">
                        <div className="absolute inset-y-0 right-0 hidden w-56 bg-[radial-gradient(circle_at_center,_rgba(15,23,42,0.08),_transparent_70%)] lg:block" />
                        <div className="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-2">
                                <Badge variant="outline">{isAdmin ? 'Admin roster control' : 'Coach roster control'}</Badge>
                                <CardTitle className="text-3xl">Roster workspace</CardTitle>
                                <CardDescription className="max-w-3xl leading-6">{scopeLabel}</CardDescription>
                            </div>

                            <CreateAssignmentDialog
                                viewerRole={viewerRole}
                                coachOptions={coachOptions}
                                athleteOptions={athleteOptions}
                                statusOptions={statusOptions}
                            />
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Assignments"
                        value={summary.totalAssignments.toString()}
                        note={`${summary.activeAssignments} active, ${summary.pausedAssignments} paused, ${summary.archivedAssignments} archived.`}
                        icon={ClipboardList}
                    />
                    <MetricCard
                        title="Athletes covered"
                        value={summary.activeAthletes.toString()}
                        note={
                            isAdmin
                                ? `${summary.representedCoaches} coaches are represented in the current roster map.`
                                : 'Athletes with an active assignment to you.'
                        }
                        icon={Users}
                    />
                    <MetricCard
                        title="Still available"
                        value={summary.availableAthletes.toString()}
                        note={isAdmin ? 'Athletes with no active coach coverage yet.' : 'Athletes not currently active on your roster yet.'}
                        icon={UserRoundPlus}
                    />
                    <MetricCard
                        title="Paused or archived"
                        value={(summary.pausedAssignments + summary.archivedAssignments).toString()}
                        note={`${summary.athletesMissingRecentCheckIns} athlete(s) are also missing a recent progress check-in.`}
                        icon={PauseCircle}
                    />
                </div>

                <Card className="border-sidebar-border/70">
                    <CardHeader>
                        <CardTitle className="text-xl">Assignment queue</CardTitle>
                        <CardDescription>
                            Every row should tell you whether the coach-athlete relationship is healthy, neglected, or dead.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {assignments.data.length === 0 ? (
                            <div className="border-sidebar-border/70 rounded-xl border border-dashed p-8 text-center">
                                <p className="font-medium">No roster assignments yet.</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">
                                    That means either the roster is genuinely empty or the operating workflow still lives in someone’s head. Both are
                                    bad.
                                </p>
                            </div>
                        ) : (
                            assignments.data.map((assignment) => (
                                <div key={assignment.id} className="border-sidebar-border/70 rounded-2xl border p-5">
                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-lg font-semibold">{assignment.athlete.name}</p>
                                                <Badge variant={badgeVariant(assignment.status)}>{humanizeStatus(assignment.status)}</Badge>
                                                {assignment.membership && (
                                                    <Badge variant={badgeVariant(assignment.membership.status)}>
                                                        {humanizeStatus(assignment.membership.status)}
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {assignment.athlete.email}
                                                {assignment.athlete.phone ? ` · ${assignment.athlete.phone}` : ''}
                                            </p>
                                            <p className="text-muted-foreground text-sm leading-6">
                                                Coach: {assignment.coach.name}
                                                {isAdmin ? ` · ${assignment.coach.email}` : ''}
                                            </p>
                                            <p className="text-sm leading-6">
                                                {assignment.goal ?? assignment.athlete.primaryGoal ?? 'No explicit coaching goal set yet.'}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="outline">{assignment.connectedDevices} connected devices</Badge>
                                            {assignment.membership && <Badge variant="outline">{assignment.membership.planName}</Badge>}
                                            <EditAssignmentDialog assignment={assignment} statusOptions={statusOptions} />
                                        </div>
                                    </div>

                                    <div className="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Membership</p>
                                            <p className="mt-2 font-medium">
                                                {assignment.membership ? formatDays(assignment.membership.daysRemaining) : 'No active membership'}
                                            </p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {assignment.membership
                                                    ? humanizeStatus(assignment.membership.status)
                                                    : 'Nothing current is attached.'}
                                            </p>
                                        </div>

                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Recovery</p>
                                            <p className="mt-2 font-medium">{formatReadiness(assignment.latestSnapshot?.readinessScore ?? null)}</p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {assignment.latestSnapshot
                                                    ? `${formatSleepHours(assignment.latestSnapshot.sleepHours)} · strain ${assignment.latestSnapshot.strainScore ?? 'N/A'}`
                                                    : 'No recovery snapshot synced yet.'}
                                            </p>
                                        </div>

                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Food and body</p>
                                            <p className="mt-2 font-medium">
                                                {assignment.latestCheckIn
                                                    ? `${assignment.latestCheckIn.weightKg === null ? 'No weight' : `${assignment.latestCheckIn.weightKg.toFixed(1)} kg`} · ${assignment.latestCheckIn.proteinGrams === null ? 'No protein' : `${assignment.latestCheckIn.proteinGrams} g protein`}`
                                                    : 'No recent check-in'}
                                            </p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {assignment.latestCheckIn
                                                    ? `${assignment.latestCheckIn.caloriesConsumed === null ? 'No calories logged' : `${assignment.latestCheckIn.caloriesConsumed} kcal`} · Water ${assignment.latestCheckIn.waterLiters === null ? 'N/A' : `${assignment.latestCheckIn.waterLiters.toFixed(1)} L`}`
                                                    : 'Weight, food, and hydration are still a blind spot here.'}
                                            </p>
                                        </div>

                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Training</p>
                                            <p className="mt-2 font-medium">{assignment.currentProgram?.title ?? 'No current program'}</p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {assignment.currentProgram
                                                    ? `Next session ${assignment.currentProgram.nextSessionDate ?? 'not scheduled'} · ${assignment.currentProgram.pendingLogs} pending log(s)`
                                                    : 'The athlete is assigned, but actual programming still needs to happen.'}
                                            </p>
                                        </div>

                                        <div className="border-sidebar-border/70 rounded-xl border p-4">
                                            <p className="text-muted-foreground text-xs tracking-[0.18em] uppercase">Timeline</p>
                                            <p className="mt-2 font-medium">Started {assignment.startedAt ?? 'not set'}</p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {assignment.endedAt ? `Ended ${assignment.endedAt}` : 'Still live unless the status says otherwise.'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                        <p className="text-muted-foreground text-sm leading-6">
                                            {assignment.notes ?? 'No notes on the assignment yet.'}
                                        </p>

                                        <div className="flex flex-wrap gap-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href="/training">
                                                    <Dumbbell className="mr-2 size-4" />
                                                    Training
                                                </Link>
                                            </Button>
                                            <Button asChild variant="outline" size="sm">
                                                <Link href="/wearables">
                                                    <Watch className="mr-2 size-4" />
                                                    Wearables
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}

                        {assignments.last_page > 1 && (
                            <div className="border-sidebar-border/70 flex items-center justify-between border-t pt-4">
                                <p className="text-muted-foreground text-sm">
                                    Page {assignments.current_page} of {assignments.last_page}
                                </p>

                                <div className="flex gap-2">
                                    <Button asChild variant="outline" size="sm" disabled={!assignments.prev_page_url}>
                                        <Link href={assignments.prev_page_url ?? route('roster.index')} preserveScroll>
                                            <ArrowLeft className="mr-2 size-4" />
                                            Previous
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm" disabled={!assignments.next_page_url}>
                                        <Link href={assignments.next_page_url ?? route('roster.index')} preserveScroll>
                                            Next
                                            <ArrowRight className="ml-2 size-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="border-sidebar-border/70">
                        <CardHeader>
                            <CardTitle className="text-lg">Status meaning</CardTitle>
                            <CardDescription>
                                The labels are simple on purpose. Complexity here is usually just bad discipline wearing a tie.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-muted-foreground space-y-3 text-sm leading-6">
                            <p className="flex items-start gap-2">
                                <Activity className="text-primary mt-1 size-4" />
                                <span>
                                    <strong className="text-foreground">Active:</strong> coaching is live, the athlete counts on the roster, and
                                    programs can be assigned.
                                </span>
                            </p>
                            <p className="flex items-start gap-2">
                                <PauseCircle className="text-primary mt-1 size-4" />
                                <span>
                                    <strong className="text-foreground">Paused:</strong> relationship is temporarily on hold, but the context should
                                    stay intact.
                                </span>
                            </p>
                            <p className="flex items-start gap-2">
                                <Archive className="text-primary mt-1 size-4" />
                                <span>
                                    <strong className="text-foreground">Archived:</strong> the relationship is over, and the timeline should be closed
                                    honestly.
                                </span>
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-sidebar-border/70">
                        <CardHeader>
                            <CardTitle className="text-lg">What this page fixes</CardTitle>
                            <CardDescription>
                                The product already depended on assignments everywhere. Now the operating workflow actually has a home.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-muted-foreground space-y-2 text-sm leading-6">
                            <p>Coaches can claim and update their roster from the UI instead of getting blocked before training even starts.</p>
                            <p>Admins can see who is covered, who is drifting, and who still has no coach attached.</p>
                            <p>
                                Membership, recovery, and training status now sit next to the relationship itself, which is where decisions actually
                                happen.
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-sidebar-border/70">
                        <CardHeader>
                            <CardTitle className="text-lg">Fast links</CardTitle>
                            <CardDescription>Move from roster status to execution without taking the scenic route.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <Button asChild variant="outline" className="w-full justify-between">
                                <Link href="/training">
                                    Training workspace
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full justify-between">
                                <Link href="/wearables">
                                    Wearable recovery board
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            {isAdmin && (
                                <Button asChild variant="outline" className="w-full justify-between">
                                    <Link href="/admin/control-center">
                                        Admin control center
                                        <Shield className="size-4" />
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
