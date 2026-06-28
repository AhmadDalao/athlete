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
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Download, Dumbbell, FileText, LineChart, Upload, UserRound } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface Option {
    value: string;
    label: string;
}

interface AthleteFileRow {
    id: number;
    displayName: string;
    originalFilename: string;
    category: string;
    visibility: string;
    status: string;
    mimeType: string | null;
    sizeBytes: number;
    notes: string | null;
    uploadedBy: string | null;
    createdAt: string | null;
    archivedAt: string | null;
}

interface AthleteShowProps {
    profile: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        primaryGoal: string | null;
        preferredContactMethod: string | null;
        registrationChannel: string | null;
        createdAt: string | null;
        latestCheckInAt: string | null;
    };
    permissions: {
        canManageFiles: boolean;
        canViewAdminProfile: boolean;
    };
    summary: {
        currentPlan: string;
        membershipStatus: string;
        daysRemaining: number | null;
        activeCoach: string;
        programs: number;
        sessions: number;
        completedSessions: number;
        partialSessions: number;
        missedSessions: number;
        files: number;
    };
    coachAssignments: Array<{
        id: number;
        coachName: string;
        coachEmail: string;
        status: string;
        goal: string | null;
        startedAt: string | null;
        endedAt: string | null;
    }>;
    memberships: Array<{
        id: number;
        planName: string;
        status: string;
        startsAt: string | null;
        renewsAt: string | null;
        endsAt: string | null;
        daysRemaining: number | null;
        price: number;
        currency: string;
    }>;
    payments: Array<{
        id: number;
        type: string;
        status: string;
        amount: number | null;
        currency: string | null;
        eventAt: string | null;
        reference: string | null;
    }>;
    devices: Array<{
        id: number;
        provider: string;
        status: string;
        lastSyncedAt: string | null;
        latestMetricDate: string | null;
        readiness: number | null;
        sleepHours: number | null;
        strain: number | null;
    }>;
    progress: Array<{
        id: number;
        loggedDate: string | null;
        weightKg: number | null;
        caloriesConsumed: number | null;
        proteinGrams: number | null;
        carbsGrams: number | null;
        fatGrams: number | null;
        waterLiters: number | null;
        sleepHours: number | null;
        energyScore: number | null;
        sorenessScore: number | null;
        notes: string | null;
    }>;
    sessions: Array<{
        id: number;
        programTitle: string;
        coachName: string;
        title: string;
        scheduledDate: string | null;
        focus: string | null;
        completionStatus: string;
        performedAt: string | null;
        durationMinutes: number | null;
        exertionRating: number | null;
        setCount: number;
        completedSets: number;
        notes: string | null;
    }>;
    files: AthleteFileRow[];
    messages: Array<{
        id: number;
        assignmentId: number;
        coachName: string;
        senderName: string | null;
        body: string;
        readAt: string | null;
        sentAt: string | null;
    }>;
    fileOptions: {
        categories: Option[];
        visibilities: Option[];
    };
}

interface UploadFormData {
    file: File | null;
    display_name: string;
    category: string;
    visibility: string;
    notes: string;
}

interface FileUpdateFormData {
    display_name: string;
    category: string;
    visibility: string;
    status: string;
    notes: string;
}

function humanize(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDays(days: number | null) {
    if (days === null) {
        return 'No end date';
    }

    if (days === 0) {
        return 'Ends today';
    }

    return `${days} day${days === 1 ? '' : 's'} left`;
}

function formatSize(bytes: number) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['active', 'completed', 'accepted'].includes(status)) {
        return 'default';
    }

    if (['partial', 'paused', 'trialing', 'grace'].includes(status)) {
        return 'secondary';
    }

    if (['missed', 'past_due', 'archived', 'expired', 'cancelled'].includes(status)) {
        return 'destructive';
    }

    return 'outline';
}

function UploadFileDialog({ athleteId, fileOptions }: { athleteId: number; fileOptions: AthleteShowProps['fileOptions'] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<UploadFormData>({
        file: null,
        display_name: '',
        category: 'admin',
        visibility: 'coach_admin',
        notes: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('athletes.files.store', athleteId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                    <Upload className="size-4" />
                    Upload file
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Upload athlete file</DialogTitle>
                    <DialogDescription>Store medical, progress, training, media, or admin files against this athlete.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="file">File</Label>
                        <Input id="file" type="file" onChange={(event) => setData('file', event.target.files?.[0] ?? null)} />
                        <InputError message={errors.file} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2 md:col-span-1">
                            <Label htmlFor="display_name">Display name</Label>
                            <Input id="display_name" value={data.display_name} onChange={(event) => setData('display_name', event.target.value)} />
                            <InputError message={errors.display_name} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {fileOptions.categories.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.category} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Visibility</Label>
                            <Select value={data.visibility} onValueChange={(value) => setData('visibility', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {fileOptions.visibilities.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.visibility} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="file-notes">Notes</Label>
                        <Textarea id="file-notes" value={data.notes} onChange={(event) => setData('notes', event.target.value)} />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing || !data.file}>
                            Save file
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditFileDialog({ file, fileOptions }: { file: AthleteFileRow; fileOptions: AthleteShowProps['fileOptions'] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm<FileUpdateFormData>({
        display_name: file.displayName,
        category: file.category,
        visibility: file.visibility,
        status: file.status,
        notes: file.notes ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(route('athlete-files.update', file.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Edit
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit file</DialogTitle>
                    <DialogDescription>Move the file between categories, change visibility, or archive it.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor={`file-name-${file.id}`}>Display name</Label>
                        <Input id={`file-name-${file.id}`} value={data.display_name} onChange={(event) => setData('display_name', event.target.value)} />
                        <InputError message={errors.display_name} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {fileOptions.categories.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.category} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Visibility</Label>
                            <Select value={data.visibility} onValueChange={(value) => setData('visibility', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {fileOptions.visibilities.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.visibility} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="archived">Archived</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor={`file-notes-${file.id}`}>Notes</Label>
                        <Textarea id={`file-notes-${file.id}`} value={data.notes} onChange={(event) => setData('notes', event.target.value)} />
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

export default function AthleteShow({
    profile,
    permissions,
    summary,
    coachAssignments,
    memberships,
    payments,
    devices,
    progress,
    sessions,
    files,
    messages,
    fileOptions,
}: AthleteShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roster', href: '/roster' },
        { title: profile.name, href: `/athletes/${profile.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${profile.name} profile`} />
            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <WorkspaceHero
                    eyebrow="Athlete profile"
                    title={profile.name}
                    description="Coach-facing source of truth for this athlete: profile, roster relationship, schedule, completed sessions, progress, devices, membership, messages, and files."
                    badges={[summary.membershipStatus, `${summary.completedSessions} completed sessions`, `${summary.files} active files`]}
                    actions={
                        <>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                <Link href="/roster">
                                    <ArrowLeft className="size-4" />
                                    Roster
                                </Link>
                            </Button>
                            {permissions.canViewAdminProfile && (
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                    <Link href={route('admin.users.show', profile.id)}>Admin profile</Link>
                                </Button>
                            )}
                            {permissions.canManageFiles && <UploadFileDialog athleteId={profile.id} fileOptions={fileOptions} />}
                        </>
                    }
                    aside={
                        <div className="grid gap-3">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Contact</p>
                                <p className="mt-3 font-semibold text-stone-950">{profile.email}</p>
                                <p className="mt-1 text-sm text-stone-600">{profile.phone ?? 'No phone'} · {profile.preferredContactMethod ?? 'email'}</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Primary goal</p>
                                <p className="mt-3 text-sm leading-6 text-stone-700">{profile.primaryGoal ?? 'No goal set yet.'}</p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard title="Membership" value={summary.currentPlan} note={`${humanize(summary.membershipStatus)} · ${formatDays(summary.daysRemaining)}`} icon={UserRound} />
                    <WorkspaceMetricCard title="Training" value={`${summary.completedSessions}/${summary.sessions}`} note={`${summary.partialSessions} partial and ${summary.missedSessions} missed sessions.`} icon={Dumbbell} />
                    <WorkspaceMetricCard title="Latest check-in" value={profile.latestCheckInAt ?? 'None'} note="Food, weight, hydration, sleep, and readiness inputs." icon={LineChart} />
                    <WorkspaceMetricCard title="Files" value={summary.files.toString()} note="Active athlete documents and media." icon={FileText} />
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading eyebrow="Roster and billing" title="Relationship and subscription tables." description="The first thing a coach needs is who owns the relationship and whether the account is commercially clean." />
                    <div className="grid gap-4 xl:grid-cols-2">
                        <WorkspacePanel title="Coach assignments" description="Coach ownership history." contentClassName="space-y-4">
                            <WorkspaceTable minWidth="min-w-[720px]">
                                <WorkspaceTableHeader labels={['Coach', 'Status', 'Goal', 'Started', 'Ended']} />
                                {coachAssignments.length === 0 ? (
                                    <WorkspaceTableEmpty message="No coach assignments found." colSpan={5} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {coachAssignments.map((assignment) => (
                                            <tr key={assignment.id}>
                                                <td className="px-4 py-3">
                                                    <p className="font-medium text-stone-950">{assignment.coachName}</p>
                                                    <p className="text-xs text-stone-500">{assignment.coachEmail}</p>
                                                </td>
                                                <td className="px-4 py-3"><Badge variant={statusVariant(assignment.status)}>{humanize(assignment.status)}</Badge></td>
                                                <td className="px-4 py-3 text-sm text-stone-600">{assignment.goal ?? 'No goal'}</td>
                                                <td className="px-4 py-3 text-sm text-stone-600">{assignment.startedAt ?? 'N/A'}</td>
                                                <td className="px-4 py-3 text-sm text-stone-600">{assignment.endedAt ?? 'Live'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </WorkspacePanel>
                        <WorkspacePanel title="Memberships" description="Subscription state and period tracking." contentClassName="space-y-4">
                            <WorkspaceTable minWidth="min-w-[760px]">
                                <WorkspaceTableHeader labels={['Plan', 'Status', 'Period', 'Days', 'Price']} />
                                {memberships.length === 0 ? (
                                    <WorkspaceTableEmpty message="No memberships found." colSpan={5} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {memberships.map((membership) => (
                                            <tr key={membership.id}>
                                                <td className="px-4 py-3 font-medium text-stone-950">{membership.planName}</td>
                                                <td className="px-4 py-3"><Badge variant={statusVariant(membership.status)}>{humanize(membership.status)}</Badge></td>
                                                <td className="px-4 py-3 text-sm text-stone-600">{membership.startsAt ?? 'N/A'} to {membership.endsAt ?? 'N/A'}</td>
                                                <td className="px-4 py-3 text-sm text-stone-600">{formatDays(membership.daysRemaining)}</td>
                                                <td className="px-4 py-3 text-sm text-stone-600">{membership.currency} {membership.price.toFixed(2)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </WorkspacePanel>
                    </div>
                </section>

                <WorkspacePanel title="Training schedule and completed sessions" description="Assigned work, completion status, set counts, and coach notes." contentClassName="space-y-4">
                    <WorkspaceTable minWidth="min-w-[1120px]">
                        <WorkspaceTableHeader labels={['Date', 'Program', 'Session', 'Focus', 'Status', 'Sets', 'Performance', 'Notes']} />
                        {sessions.length === 0 ? (
                            <WorkspaceTableEmpty message="No training sessions found." colSpan={8} />
                        ) : (
                            <tbody className="divide-y divide-stone-100">
                                {sessions.map((session) => (
                                    <tr key={session.id} className="align-top">
                                        <td className="px-4 py-3 text-sm text-stone-600">{session.scheduledDate ?? 'N/A'}</td>
                                        <td className="px-4 py-3 font-medium text-stone-950">{session.programTitle}</td>
                                        <td className="px-4 py-3 text-sm text-stone-700">{session.title}</td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{session.focus ?? 'General'}</td>
                                        <td className="px-4 py-3"><Badge variant={statusVariant(session.completionStatus)}>{humanize(session.completionStatus)}</Badge></td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{session.completedSets}/{session.setCount}</td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{session.durationMinutes ?? 'No duration'} min · RPE {session.exertionRating ?? 'N/A'}</td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{session.notes ?? 'No notes'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        )}
                    </WorkspaceTable>
                </WorkspacePanel>

                <section className="grid gap-4 xl:grid-cols-2">
                    <WorkspacePanel title="Progress check-ins" description="Food, body, hydration, sleep, soreness, and energy." contentClassName="space-y-4">
                        <WorkspaceTable minWidth="min-w-[980px]">
                            <WorkspaceTableHeader labels={['Date', 'Weight', 'Food', 'Hydration', 'Sleep', 'Feel', 'Notes']} />
                            {progress.length === 0 ? (
                                <WorkspaceTableEmpty message="No progress check-ins found." colSpan={7} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {progress.map((row) => (
                                        <tr key={row.id} className="align-top">
                                            <td className="px-4 py-3 text-sm text-stone-600">{row.loggedDate ?? 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{row.weightKg ?? 'N/A'} kg</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{row.caloriesConsumed ?? 'N/A'} kcal · P {row.proteinGrams ?? 'N/A'}g · C {row.carbsGrams ?? 'N/A'}g · F {row.fatGrams ?? 'N/A'}g</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{row.waterLiters ?? 'N/A'} L</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{row.sleepHours ?? 'N/A'} h</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">Energy {row.energyScore ?? 'N/A'} · Soreness {row.sorenessScore ?? 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{row.notes ?? 'No notes'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                    <WorkspacePanel title="Wearables" description="Device connection and latest recovery snapshot." contentClassName="space-y-4">
                        <WorkspaceTable minWidth="min-w-[820px]">
                            <WorkspaceTableHeader labels={['Provider', 'Status', 'Last sync', 'Metric date', 'Readiness', 'Sleep', 'Strain']} />
                            {devices.length === 0 ? (
                                <WorkspaceTableEmpty message="No device connections found." colSpan={7} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {devices.map((device) => (
                                        <tr key={device.id}>
                                            <td className="px-4 py-3 font-medium text-stone-950">{device.provider}</td>
                                            <td className="px-4 py-3"><Badge variant={statusVariant(device.status)}>{humanize(device.status)}</Badge></td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{device.lastSyncedAt ?? 'Never'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{device.latestMetricDate ?? 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{device.readiness ?? 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{device.sleepHours ?? 'N/A'} h</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{device.strain ?? 'N/A'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>

                <WorkspacePanel title="Athlete files" description="Documents, media, progress files, and admin-only records." contentClassName="space-y-4">
                    <WorkspaceTable minWidth="min-w-[1180px]">
                        <WorkspaceTableHeader labels={['File', 'Category', 'Visibility', 'Status', 'Size', 'Uploaded', 'Notes', 'Actions']} />
                        {files.length === 0 ? (
                            <WorkspaceTableEmpty message="No files uploaded for this athlete yet." colSpan={8} />
                        ) : (
                            <tbody className="divide-y divide-stone-100">
                                {files.map((file) => (
                                    <tr key={file.id} className="align-top">
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-stone-950">{file.displayName}</p>
                                            <p className="text-xs text-stone-500">{file.originalFilename}</p>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{humanize(file.category)}</td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{humanize(file.visibility)}</td>
                                        <td className="px-4 py-3"><Badge variant={statusVariant(file.status)}>{humanize(file.status)}</Badge></td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{formatSize(file.sizeBytes)}</td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{file.createdAt ?? 'N/A'} by {file.uploadedBy ?? 'System'}</td>
                                        <td className="px-4 py-3 text-sm text-stone-600">{file.notes ?? 'No notes'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-col gap-2">
                                                <Button asChild size="sm" variant="outline">
                                                    <a href={route('athlete-files.download', file.id)}>
                                                        <Download className="size-4" />
                                                        Download
                                                    </a>
                                                </Button>
                                                {permissions.canManageFiles && <EditFileDialog file={file} fileOptions={fileOptions} />}
                                                {permissions.canManageFiles && file.status === 'active' && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-red-200 text-red-700 hover:bg-red-50"
                                                        onClick={() => router.post(route('athlete-files.archive', file.id), {}, { preserveScroll: true })}
                                                    >
                                                        Archive
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        )}
                    </WorkspaceTable>
                </WorkspacePanel>

                <section className="grid gap-4 xl:grid-cols-2">
                    <WorkspacePanel title="Recent payments" description="Latest billing events tied to this athlete." contentClassName="space-y-4">
                        <WorkspaceTable minWidth="min-w-[760px]">
                            <WorkspaceTableHeader labels={['Time', 'Type', 'Status', 'Amount', 'Reference']} />
                            {payments.length === 0 ? (
                                <WorkspaceTableEmpty message="No payment events found." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {payments.map((payment) => (
                                        <tr key={payment.id}>
                                            <td className="px-4 py-3 text-sm text-stone-600">{payment.eventAt ?? 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{humanize(payment.type)}</td>
                                            <td className="px-4 py-3"><Badge variant={statusVariant(payment.status)}>{humanize(payment.status)}</Badge></td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{payment.amount ?? 'N/A'} {payment.currency ?? ''}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{payment.reference ?? 'N/A'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                    <WorkspacePanel title="Recent messages" description="Latest coach-athlete messages connected to roster assignments." contentClassName="space-y-4">
                        <WorkspaceTable minWidth="min-w-[760px]">
                            <WorkspaceTableHeader labels={['Time', 'Coach', 'Sender', 'Message', 'Read']} />
                            {messages.length === 0 ? (
                                <WorkspaceTableEmpty message="No messages found." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {messages.map((message) => (
                                        <tr key={message.id} className="align-top">
                                            <td className="px-4 py-3 text-sm text-stone-600">{message.sentAt ?? 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{message.coachName}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{message.senderName ?? 'System'}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{message.body}</td>
                                            <td className="px-4 py-3 text-sm text-stone-600">{message.readAt ?? 'Unread'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>
            </div>
        </AppLayout>
    );
}
