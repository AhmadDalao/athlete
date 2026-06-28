import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
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
    Activity,
    Archive,
    ArrowLeft,
    ArrowRight,
    ClipboardList,
    Dumbbell,
    MailPlus,
    PauseCircle,
    Shield,
    UserRoundPlus,
    Users,
    Watch,
} from 'lucide-react';
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
    weeklyBrief?: {
        priority: string;
        score: number;
        headline: string;
        summary: string;
        reasons: string[];
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

function priorityBadgeVariant(priority: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (priority === 'high') {
        return 'destructive';
    }

    if (priority === 'medium') {
        return 'secondary';
    }

    if (priority === 'stable') {
        return 'default';
    }

    return 'outline';
}

function todayDate() {
    return new Date().toISOString().slice(0, 10);
}

function normalizedWeeklyBrief(assignment: AssignmentRow) {
    const fallback = {
        priority: 'stable',
        score: 0,
        headline: 'Weekly coach brief is not available yet.',
        summary: 'The live payload is missing the weekly decision brief. The page should still load instead of falling over.',
        reasons: ['Weekly brief data has not been synced into this environment yet.'],
    };

    if (!assignment.weeklyBrief || typeof assignment.weeklyBrief.priority !== 'string') {
        return fallback;
    }

    return {
        priority: assignment.weeklyBrief.priority,
        score: assignment.weeklyBrief.score ?? 0,
        headline: assignment.weeklyBrief.headline ?? fallback.headline,
        summary: assignment.weeklyBrief.summary ?? fallback.summary,
        reasons:
            Array.isArray(assignment.weeklyBrief.reasons) && assignment.weeklyBrief.reasons.length > 0
                ? assignment.weeklyBrief.reasons
                : fallback.reasons,
    };
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
    const heroBadges = [`${summary.activeAssignments} active assignments`, `${summary.athletesMissingRecentCheckIns} missing recent check-ins`];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roster" />

            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow={isAdmin ? 'Admin roster control' : 'Coach roster control'}
                    title={
                        isAdmin ? 'Every coaching relationship should be visible in one pass.' : 'Run the roster without guessing who needs you next.'
                    }
                    description={`${scopeLabel} The roster should tell you who is covered, who is drifting, and where the next action belongs before you open anything else.`}
                    badges={heroBadges}
                    actions={
                        <>
                            <CreateAssignmentDialog
                                viewerRole={viewerRole}
                                coachOptions={coachOptions}
                                athleteOptions={athleteOptions}
                                statusOptions={statusOptions}
                            />
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href={route('roster.invitations.index')}>
                                    <MailPlus className="mr-2 size-4" />
                                    Invite athletes
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/training">
                                    <Dumbbell className="mr-2 size-4" />
                                    Open training
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/progress">
                                    <Activity className="mr-2 size-4" />
                                    Open progress
                                </Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Active athletes</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{summary.activeAthletes}</p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {isAdmin ? 'Athletes currently attached to a live coach relationship.' : 'Athletes currently assigned to you.'}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Coverage gaps</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{summary.availableAthletes}</p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {isAdmin ? 'Athletes without a live coach assignment yet.' : 'Athletes not currently active on your roster.'}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Assignments</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{summary.totalAssignments}</p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {summary.pausedAssignments} paused and {summary.archivedAssignments} archived.
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Coach spread</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {isAdmin ? summary.representedCoaches : 1}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {isAdmin ? 'Coaches represented in the visible roster map.' : 'You are the visible coach in this view.'}
                                </p>
                            </div>
                        </div>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Assignments"
                        value={summary.totalAssignments.toString()}
                        note={`${summary.activeAssignments} active, ${summary.pausedAssignments} paused, ${summary.archivedAssignments} archived.`}
                        icon={ClipboardList}
                    />
                    <WorkspaceMetricCard
                        title="Athletes covered"
                        value={summary.activeAthletes.toString()}
                        note={
                            isAdmin
                                ? `${summary.representedCoaches} coaches are represented in the current roster map.`
                                : 'Athletes with an active assignment to you.'
                        }
                        icon={Users}
                    />
                    <WorkspaceMetricCard
                        title="Still available"
                        value={summary.availableAthletes.toString()}
                        note={isAdmin ? 'Athletes with no active coach coverage yet.' : 'Athletes not currently active on your roster yet.'}
                        icon={UserRoundPlus}
                    />
                    <WorkspaceMetricCard
                        title="Paused or archived"
                        value={(summary.pausedAssignments + summary.archivedAssignments).toString()}
                        note={`${summary.athletesMissingRecentCheckIns} athlete(s) are also missing a recent progress check-in.`}
                        icon={PauseCircle}
                    />
                </div>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Assignment queue"
                        title="See the relationship, the risk, and the next action in one place."
                        description="Each assignment should surface membership, recovery, progress, and program state without forcing you to open four different pages just to understand one athlete."
                    />
                    <WorkspacePanel
                        title="Live roster map"
                        description={`${assignments.total} assignment record(s) in the current view. Healthy relationships should read fast. Bad ones should be impossible to miss.`}
                        contentClassName="space-y-4"
                    >
                        <WorkspaceTable minWidth="min-w-[1320px]">
                            <WorkspaceTableHeader
                                labels={['Athlete', 'Coach', 'Status', 'Membership', 'Recovery', 'Food / body', 'Training', 'Timeline', 'Actions']}
                            />
                            {assignments.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No roster assignments yet." colSpan={9} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {assignments.data.map((assignment) => {
                                        const weeklyBrief = normalizedWeeklyBrief(assignment);

                                        return (
                                            <tr key={assignment.id} className="align-top transition-colors hover:bg-stone-50/80">
                                                <td className="px-5 py-4">
                                                    <p className="font-semibold text-stone-950">{assignment.athlete.name}</p>
                                                    <p className="mt-1 text-xs text-stone-500">{assignment.athlete.email}</p>
                                                    <p className="mt-2 line-clamp-2 max-w-[16rem] text-xs text-stone-600">
                                                        {assignment.goal ?? assignment.athlete.primaryGoal ?? 'No goal set.'}
                                                    </p>
                                                    <Button asChild variant="link" size="sm" className="mt-2 h-auto p-0 text-stone-950">
                                                        <Link href={route('athletes.show', assignment.athlete.id)}>View athlete profile</Link>
                                                    </Button>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-stone-950">{assignment.coach.name}</p>
                                                    {isAdmin && <p className="mt-1 text-xs text-stone-500">{assignment.coach.email}</p>}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="flex flex-col items-start gap-2">
                                                        <Badge variant={badgeVariant(assignment.status)}>{humanizeStatus(assignment.status)}</Badge>
                                                        <Badge variant={priorityBadgeVariant(weeklyBrief.priority)}>
                                                            {humanizeStatus(weeklyBrief.priority)}
                                                        </Badge>
                                                    </div>
                                                </td>
                                                <td className="px-5 py-4">
                                                    {assignment.membership ? (
                                                        <div className="space-y-1">
                                                            <Badge variant={badgeVariant(assignment.membership.status)}>
                                                                {humanizeStatus(assignment.membership.status)}
                                                            </Badge>
                                                            <p className="text-xs font-medium text-stone-950">{assignment.membership.planName}</p>
                                                            <p className="text-xs text-stone-600">{formatDays(assignment.membership.daysRemaining)}</p>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-stone-500">No active membership</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-stone-950">
                                                        {formatReadiness(assignment.latestSnapshot?.readinessScore ?? null)}
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        {assignment.latestSnapshot
                                                            ? `${formatSleepHours(assignment.latestSnapshot.sleepHours)} sleep · strain ${assignment.latestSnapshot.strainScore ?? 'N/A'}`
                                                            : 'No snapshot'}
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">{assignment.connectedDevices} device(s)</p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-stone-950">
                                                        {assignment.latestCheckIn?.weightKg === null || assignment.latestCheckIn?.weightKg === undefined
                                                            ? 'No weight'
                                                            : `${assignment.latestCheckIn.weightKg.toFixed(1)} kg`}
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        {assignment.latestCheckIn
                                                            ? `${assignment.latestCheckIn.caloriesConsumed ?? 'No'} kcal · ${assignment.latestCheckIn.proteinGrams ?? 'No'}g protein`
                                                            : 'No recent check-in'}
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        Water{' '}
                                                        {assignment.latestCheckIn?.waterLiters === null ||
                                                        assignment.latestCheckIn?.waterLiters === undefined
                                                            ? 'N/A'
                                                            : `${assignment.latestCheckIn.waterLiters.toFixed(1)} L`}
                                                    </p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-stone-950">
                                                        {assignment.currentProgram?.title ?? 'No current program'}
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        {assignment.currentProgram
                                                            ? `Next ${assignment.currentProgram.nextSessionDate ?? 'not scheduled'} · ${assignment.currentProgram.pendingLogs} pending`
                                                            : 'Programming needed'}
                                                    </p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-stone-950">Start {assignment.startedAt ?? 'not set'}</p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        {assignment.endedAt ? `End ${assignment.endedAt}` : 'Still live'}
                                                    </p>
                                                    <p className="mt-2 line-clamp-2 max-w-[14rem] text-xs text-stone-600">{weeklyBrief.headline}</p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="flex flex-col gap-2">
                                                        <Button asChild variant="default" size="sm">
                                                            <Link href={route('athletes.show', assignment.athlete.id)}>Open profile</Link>
                                                        </Button>
                                                        <EditAssignmentDialog assignment={assignment} statusOptions={statusOptions} />
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
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            )}
                        </WorkspaceTable>

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
                    </WorkspacePanel>
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Operating rules"
                        title="Keep the roster simple enough that people actually maintain it."
                        description="Roster systems get ugly when nobody agrees what the statuses mean or where assignment context belongs. This page exists to kill that ambiguity."
                    />
                    <div className="grid gap-4 xl:grid-cols-3">
                        <WorkspacePanel
                            title="Status meaning"
                            description="The labels stay blunt on purpose. This is operating state, not poetry."
                            contentClassName="text-muted-foreground space-y-3 text-sm leading-6"
                        >
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
                        </WorkspacePanel>

                        <WorkspacePanel
                            title="What this page fixes"
                            description="Assignments already drove the product logic. Now the humans finally get a page worthy of that fact."
                            className="xl:col-span-1"
                            contentClassName="text-muted-foreground space-y-2 text-sm leading-6"
                        >
                            <p>Coaches can claim and update their roster from the UI instead of getting blocked before training even starts.</p>
                            <p>Admins can see who is covered, who is drifting, and who still has no coach attached.</p>
                            <p>
                                Membership, recovery, and training status now sit next to the relationship itself, which is where decisions actually
                                happen.
                            </p>
                        </WorkspacePanel>

                        <WorkspacePanel
                            title="Fast links"
                            description="Move from roster status to execution without taking the scenic route."
                            contentClassName="space-y-2"
                        >
                            <Button asChild variant="outline" className="w-full justify-between">
                                <Link href={route('roster.invitations.index')}>
                                    Invitation table
                                    <MailPlus className="size-4" />
                                </Link>
                            </Button>
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
                        </WorkspacePanel>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
