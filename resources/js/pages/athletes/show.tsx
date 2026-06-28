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
import { useAutoFilter } from '@/hooks/use-auto-filter';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Download, Dumbbell, Eye, LineChart, Search, Upload, UserRound } from 'lucide-react';
import { type FormEvent, type ReactNode, useState } from 'react';

interface Option {
    value: string;
    label: string;
}

interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
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
    previewable: boolean;
}

interface FilterState {
    assignments: { q: string | null; status: string | null };
    memberships: { q: string | null; status: string | null };
    payments: { q: string | null; status: string | null; type: string | null; from: string | null; to: string | null };
    devices: { q: string | null; status: string | null; provider: string | null };
    sessions: { q: string | null; status: string | null; from: string | null; to: string | null };
    setLogs: { q: string | null; completed: string | null; from: string | null; to: string | null };
    progress: { q: string | null; from: string | null; to: string | null };
    files: { q: string | null; status: string | null; category: string | null; visibility: string | null };
    messages: { q: string | null; read: string | null };
}

interface QueryPayload {
    [key: string]: string;
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
    filters: FilterState;
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
    tables: {
        assignments: Paginated<{
            id: number;
            coachName: string;
            coachEmail: string | null;
            status: string;
            goal: string | null;
            startedAt: string | null;
            endedAt: string | null;
        }>;
        memberships: Paginated<{
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
        payments: Paginated<{
            id: number;
            type: string;
            status: string;
            provider: string | null;
            amount: number | null;
            currency: string | null;
            eventAt: string | null;
            reference: string | null;
            notes: string | null;
        }>;
        devices: Paginated<{
            id: number;
            provider: string;
            providerValue: string;
            status: string;
            externalUserId: string | null;
            lastSyncedAt: string | null;
            latestMetricDate: string | null;
            readiness: number | null;
            sleepHours: number | null;
            strain: number | null;
            lastErrorMessage: string | null;
        }>;
        sessions: Paginated<{
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
        setLogs: Paginated<{
            id: number;
            sessionId: number;
            scheduledDate: string | null;
            programTitle: string;
            sessionTitle: string;
            exerciseName: string;
            exerciseIndex: number;
            setNumber: number;
            targetReps: string | null;
            targetLoad: string | null;
            targetRestSeconds: number | null;
            actualReps: string | null;
            actualLoad: string | null;
            actualRpe: number | null;
            completedAt: string | null;
            notes: string | null;
        }>;
        progress: Paginated<{
            id: number;
            loggedDate: string | null;
            weightKg: number | null;
            bodyFatPercentage: number | null;
            waistCm: number | null;
            caloriesConsumed: number | null;
            proteinGrams: number | null;
            carbsGrams: number | null;
            fatGrams: number | null;
            waterLiters: number | null;
            mealsLoggedCount: number | null;
            energyScore: number | null;
            sorenessScore: number | null;
            stressScore: number | null;
            sleepQualityScore: number | null;
            notes: string | null;
        }>;
        files: Paginated<AthleteFileRow>;
        messages: Paginated<{
            id: number;
            assignmentId: number;
            coachName: string;
            senderName: string | null;
            recipientName: string | null;
            body: string;
            readAt: string | null;
            sentAt: string | null;
        }>;
    };
    fileOptions: {
        categories: Option[];
        visibilities: Option[];
        statuses: Option[];
    };
    completionStatuses: Option[];
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

interface FilterControl {
    key: string;
    label: string;
    type: 'search' | 'select' | 'date';
    placeholder?: string;
    options?: Option[];
}

const statusOptions: Option[] = [
    { value: 'active', label: 'Active' },
    { value: 'paused', label: 'Paused' },
    { value: 'archived', label: 'Archived' },
    { value: 'trialing', label: 'Trialing' },
    { value: 'grace', label: 'Grace' },
    { value: 'past_due', label: 'Past due' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'expired', label: 'Expired' },
];

const paymentStatusOptions: Option[] = [
    { value: 'pending', label: 'Pending' },
    { value: 'succeeded', label: 'Succeeded' },
    { value: 'failed', label: 'Failed' },
    { value: 'info', label: 'Info' },
];

const paymentTypeOptions: Option[] = [
    { value: 'invoice', label: 'Invoice' },
    { value: 'charge', label: 'Charge' },
    { value: 'refund', label: 'Refund' },
    { value: 'membership_change', label: 'Membership change' },
    { value: 'manual_adjustment', label: 'Manual adjustment' },
];

const deviceStatusOptions: Option[] = [
    { value: 'connected', label: 'Connected' },
    { value: 'attention', label: 'Needs attention' },
    { value: 'disconnected', label: 'Disconnected' },
];

const providerOptions: Option[] = [
    { value: 'whoop', label: 'WHOOP' },
    { value: 'garmin', label: 'Garmin' },
    { value: 'oura', label: 'Oura' },
    { value: 'strava', label: 'Strava' },
];

const completionFilterOptions: Option[] = [
    { value: 'not_logged', label: 'Not logged' },
    { value: 'completed', label: 'Completed' },
    { value: 'partial', label: 'Partial' },
    { value: 'missed', label: 'Missed' },
];

const setCompletionOptions: Option[] = [
    { value: 'completed', label: 'Completed' },
    { value: 'open', label: 'Open' },
];

const readOptions: Option[] = [
    { value: 'read', label: 'Read' },
    { value: 'unread', label: 'Unread' },
];

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

function formatRest(seconds: number | null) {
    if (seconds === null) {
        return 'N/A';
    }

    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return remainingSeconds === 0 ? `${minutes}m` : `${minutes}m ${remainingSeconds}s`;
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['active', 'completed', 'accepted', 'succeeded', 'connected', 'read'].includes(status)) {
        return 'default';
    }

    if (['partial', 'paused', 'trialing', 'grace', 'pending', 'attention'].includes(status)) {
        return 'secondary';
    }

    if (['missed', 'past_due', 'archived', 'expired', 'cancelled', 'failed', 'disconnected', 'unread'].includes(status)) {
        return 'destructive';
    }

    return 'outline';
}

function initialQuery(filters: FilterState): QueryPayload {
    return {
        assignments_q: filters.assignments.q ?? '',
        assignments_status: filters.assignments.status ?? 'all',
        memberships_q: filters.memberships.q ?? '',
        memberships_status: filters.memberships.status ?? 'all',
        payments_q: filters.payments.q ?? '',
        payments_status: filters.payments.status ?? 'all',
        payments_type: filters.payments.type ?? 'all',
        payments_from: filters.payments.from ?? '',
        payments_to: filters.payments.to ?? '',
        devices_q: filters.devices.q ?? '',
        devices_status: filters.devices.status ?? 'all',
        devices_provider: filters.devices.provider ?? 'all',
        sessions_q: filters.sessions.q ?? '',
        sessions_status: filters.sessions.status ?? 'all',
        sessions_from: filters.sessions.from ?? '',
        sessions_to: filters.sessions.to ?? '',
        sets_q: filters.setLogs.q ?? '',
        sets_completed: filters.setLogs.completed ?? 'all',
        sets_from: filters.setLogs.from ?? '',
        sets_to: filters.setLogs.to ?? '',
        progress_q: filters.progress.q ?? '',
        progress_from: filters.progress.from ?? '',
        progress_to: filters.progress.to ?? '',
        files_q: filters.files.q ?? '',
        files_status: filters.files.status ?? 'all',
        files_category: filters.files.category ?? 'all',
        files_visibility: filters.files.visibility ?? 'all',
        messages_q: filters.messages.q ?? '',
        messages_read: filters.messages.read ?? 'all',
    };
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
                        <div className="grid gap-2">
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
                                    {fileOptions.statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
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

function FilePreviewDialog({ file }: { file: AthleteFileRow }) {
    const previewUrl = route('athlete-files.preview', file.id);
    const image = file.mimeType?.startsWith('image/');

    if (!file.previewable) {
        return null;
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Eye className="size-4" />
                    Preview
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-5xl">
                <DialogHeader>
                    <DialogTitle>{file.displayName}</DialogTitle>
                    <DialogDescription>{file.originalFilename}</DialogDescription>
                </DialogHeader>
                <div className="max-h-[72vh] overflow-hidden rounded-2xl border border-stone-200 bg-stone-50">
                    {image ? (
                        <img src={previewUrl} alt={file.displayName} className="max-h-[72vh] w-full object-contain" />
                    ) : (
                        <iframe title={file.displayName} src={previewUrl} className="h-[72vh] w-full bg-white" />
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function Filters({
    controls,
    query,
    setField,
}: {
    controls: FilterControl[];
    query: QueryPayload;
    setField: (key: string, value: string) => void;
}) {
    if (controls.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-3 md:grid-cols-2 xl:grid-cols-4">
            {controls.map((control) => (
                <div key={control.key} className="grid gap-2">
                    <Label htmlFor={control.key}>{control.label}</Label>
                    {control.type === 'search' && (
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                            <Input
                                id={control.key}
                                value={query[control.key] ?? ''}
                                onChange={(event) => setField(control.key, event.target.value)}
                                className="bg-white pl-9"
                                placeholder={control.placeholder}
                            />
                        </div>
                    )}
                    {control.type === 'date' && (
                        <Input
                            id={control.key}
                            type="date"
                            value={query[control.key] ?? ''}
                            onChange={(event) => setField(control.key, event.target.value)}
                            className="bg-white"
                        />
                    )}
                    {control.type === 'select' && (
                        <Select value={query[control.key] ?? 'all'} onValueChange={(value) => setField(control.key, value)}>
                            <SelectTrigger className="bg-white">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                {(control.options ?? []).map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </div>
            ))}
        </div>
    );
}

function PaginationControls<T>({ paginator }: { paginator: Paginated<T> }) {
    return (
        <div className="flex flex-col gap-3 border-t border-stone-200 pt-4 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between">
            <p>
                Showing {paginator.from ?? 0} to {paginator.to ?? 0} of {paginator.total} entries
            </p>
            {paginator.last_page > 1 && (
                <div className="flex gap-2">
                    <Button asChild variant="outline" size="sm" disabled={!paginator.prev_page_url}>
                        <Link href={paginator.prev_page_url ?? '#'} preserveScroll preserveState>
                            Previous
                        </Link>
                    </Button>
                    <span className="rounded-md border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-700">
                        Page {paginator.current_page} of {paginator.last_page}
                    </span>
                    <Button asChild variant="outline" size="sm" disabled={!paginator.next_page_url}>
                        <Link href={paginator.next_page_url ?? '#'} preserveScroll preserveState>
                            Next
                        </Link>
                    </Button>
                </div>
            )}
        </div>
    );
}

function TableSection<T>({
    title,
    description,
    section,
    paginator,
    query,
    setField,
    controls,
    minWidth,
    headers,
    empty,
    children,
}: {
    title: string;
    description: string;
    section: string;
    paginator: Paginated<T>;
    query: QueryPayload;
    setField: (key: string, value: string) => void;
    controls: FilterControl[];
    minWidth: string;
    headers: string[];
    empty: string;
    children: ReactNode;
}) {
    return (
        <WorkspacePanel
            title={title}
            description={`${description} ${paginator.total} record(s) match the current filters.`}
            contentClassName="space-y-4"
        >
            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <Filters controls={controls} query={query} setField={setField} />
                <Button asChild variant="outline" className="w-full shrink-0 border-stone-300 bg-white lg:w-auto">
                    <a href={route('athletes.exports.show', { user: query.user_id, section, ...query })}>
                        <Download className="size-4" />
                        Export CSV
                    </a>
                </Button>
            </div>
            <WorkspaceTable minWidth={minWidth}>
                <WorkspaceTableHeader labels={headers} />
                {paginator.data.length === 0 ? <WorkspaceTableEmpty message={empty} colSpan={headers.length} /> : children}
            </WorkspaceTable>
            <PaginationControls paginator={paginator} />
        </WorkspacePanel>
    );
}

export default function AthleteShow({
    profile,
    permissions,
    filters,
    summary,
    tables,
    fileOptions,
}: AthleteShowProps) {
    const baseRoute = route('athletes.show', profile.id);
    const [query, setQuery] = useState<QueryPayload>(() => ({ ...initialQuery(filters), user_id: String(profile.id) }));
    const setField = (key: string, value: string) => setQuery((current) => ({ ...current, [key]: value }));

    useAutoFilter({ url: baseRoute, payload: query, only: ['filters', 'summary', 'tables'], debounceMs: 220 });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roster', href: '/roster' },
        { title: profile.name, href: `/athletes/${profile.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${profile.name} profile`} />
            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow="Athlete source of truth"
                    title={profile.name}
                    description="Everything important about this athlete in clean tables: roster, schedule, set execution, progress, devices, payments, messages, and files."
                    badges={[summary.membershipStatus, `${summary.completedSessions} completed`, `${summary.files} files`]}
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
                            <div className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Contact</p>
                                <p className="mt-3 font-semibold text-stone-950">{profile.email}</p>
                                <p className="mt-1 text-sm text-stone-600">
                                    {profile.phone ?? 'No phone'} · {profile.preferredContactMethod ?? 'email'}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Primary goal</p>
                                <p className="mt-3 text-sm leading-6 text-stone-700">{profile.primaryGoal ?? 'No goal set yet.'}</p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard title="Current plan" value={summary.currentPlan} note={formatDays(summary.daysRemaining)} icon={CalendarDays} />
                    <WorkspaceMetricCard title="Active coach" value={summary.activeCoach} note="Current roster relationship." icon={UserRound} />
                    <WorkspaceMetricCard title="Training" value={`${summary.completedSessions}/${summary.sessions}`} note={`${summary.partialSessions} partial, ${summary.missedSessions} missed.`} icon={Dumbbell} />
                    <WorkspaceMetricCard title="Latest check-in" value={profile.latestCheckInAt ?? 'None'} note={`Joined ${profile.createdAt ?? 'N/A'}.`} icon={LineChart} />
                </section>

                <WorkspaceSectionHeading
                    eyebrow="Profile tables"
                    title="Track records in rows, not cards."
                    description="Filters update automatically. Export buttons download the same table data with the same access rules."
                />

                <section className="grid gap-6">
                    <TableSection
                        title="Coach assignments"
                        description="Roster ownership, status, and relationship goals."
                        section="assignments"
                        paginator={tables.assignments}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'assignments_q', label: 'Search', type: 'search', placeholder: 'Coach, email, goal' },
                            { key: 'assignments_status', label: 'Status', type: 'select', options: statusOptions },
                        ]}
                        minWidth="min-w-[760px]"
                        headers={['Coach', 'Status', 'Goal', 'Started', 'Ended']}
                        empty="No coach assignments found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.assignments.data.map((assignment) => (
                                <tr key={assignment.id}>
                                    <td className="px-5 py-4">
                                        <p className="font-medium text-stone-950">{assignment.coachName}</p>
                                        <p className="text-xs text-stone-500">{assignment.coachEmail}</p>
                                    </td>
                                    <td className="px-5 py-4">
                                        <Badge variant={statusVariant(assignment.status)}>{humanize(assignment.status)}</Badge>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{assignment.goal ?? 'No goal'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{assignment.startedAt ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{assignment.endedAt ?? 'Active'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Memberships"
                        description="Plans, status, price, and remaining access."
                        section="memberships"
                        paginator={tables.memberships}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'memberships_q', label: 'Search', type: 'search', placeholder: 'Plan or provider' },
                            { key: 'memberships_status', label: 'Status', type: 'select', options: statusOptions },
                        ]}
                        minWidth="min-w-[860px]"
                        headers={['Plan', 'Status', 'Period', 'Days', 'Price']}
                        empty="No memberships found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.memberships.data.map((membership) => (
                                <tr key={membership.id}>
                                    <td className="px-5 py-4 font-medium text-stone-950">{membership.planName}</td>
                                    <td className="px-5 py-4">
                                        <Badge variant={statusVariant(membership.status)}>{humanize(membership.status)}</Badge>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {membership.startsAt ?? 'N/A'} to {membership.endsAt ?? 'N/A'}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{formatDays(membership.daysRemaining)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {membership.currency} {membership.price.toFixed(2)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Training sessions"
                        description="Assigned work, completion status, set counts, and coach notes."
                        section="sessions"
                        paginator={tables.sessions}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'sessions_q', label: 'Search', type: 'search', placeholder: 'Program, session, focus' },
                            { key: 'sessions_status', label: 'Status', type: 'select', options: completionFilterOptions },
                            { key: 'sessions_from', label: 'From', type: 'date' },
                            { key: 'sessions_to', label: 'To', type: 'date' },
                        ]}
                        minWidth="min-w-[1180px]"
                        headers={['Date', 'Program', 'Session', 'Focus', 'Status', 'Sets', 'Performance', 'Notes', 'Open']}
                        empty="No training sessions found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.sessions.data.map((session) => (
                                <tr key={session.id} className="align-top">
                                    <td className="px-5 py-4 text-sm text-stone-600">{session.scheduledDate ?? 'N/A'}</td>
                                    <td className="px-5 py-4">
                                        <p className="font-medium text-stone-950">{session.programTitle}</p>
                                        <p className="text-xs text-stone-500">{session.coachName}</p>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-700">{session.title}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{session.focus ?? 'General'}</td>
                                    <td className="px-5 py-4">
                                        <Badge variant={statusVariant(session.completionStatus)}>{humanize(session.completionStatus)}</Badge>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {session.completedSets}/{session.setCount}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {session.durationMinutes ?? 'No duration'} min · RPE {session.exertionRating ?? 'N/A'}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{session.notes ?? 'No notes'}</td>
                                    <td className="px-5 py-4">
                                        <Button asChild size="sm" variant="outline">
                                            <Link href="/training">Open</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Workout set execution"
                        description="Actual athlete app execution: target, actual, RPE, completion, and notes."
                        section="set-logs"
                        paginator={tables.setLogs}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'sets_q', label: 'Search', type: 'search', placeholder: 'Exercise, session, notes' },
                            { key: 'sets_completed', label: 'Completion', type: 'select', options: setCompletionOptions },
                            { key: 'sets_from', label: 'From', type: 'date' },
                            { key: 'sets_to', label: 'To', type: 'date' },
                        ]}
                        minWidth="min-w-[1360px]"
                        headers={['Date', 'Session', 'Exercise', 'Set', 'Target', 'Actual', 'RPE', 'Completed', 'Notes']}
                        empty="No per-set workout execution has been recorded yet."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.setLogs.data.map((setLog) => (
                                <tr key={setLog.id} className="align-top">
                                    <td className="px-5 py-4 text-sm text-stone-600">{setLog.scheduledDate ?? 'N/A'}</td>
                                    <td className="px-5 py-4">
                                        <p className="font-medium text-stone-950">{setLog.sessionTitle}</p>
                                        <p className="text-xs text-stone-500">{setLog.programTitle}</p>
                                    </td>
                                    <td className="px-5 py-4">
                                        <p className="font-medium text-stone-950">{setLog.exerciseName}</p>
                                        <p className="text-xs text-stone-500">Exercise {setLog.exerciseIndex + 1}</p>
                                    </td>
                                    <td className="px-5 py-4 text-sm font-semibold text-stone-950">{setLog.setNumber}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        <p>Reps/time: {setLog.targetReps ?? 'N/A'}</p>
                                        <p>Load: {setLog.targetLoad ?? 'N/A'}</p>
                                        <p>Rest: {formatRest(setLog.targetRestSeconds)}</p>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        <p>Reps/time: {setLog.actualReps ?? 'N/A'}</p>
                                        <p>Load: {setLog.actualLoad ?? 'N/A'}</p>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{setLog.actualRpe ?? 'N/A'}</td>
                                    <td className="px-5 py-4">
                                        <Badge variant={setLog.completedAt ? 'default' : 'outline'}>{setLog.completedAt ? 'Completed' : 'Open'}</Badge>
                                        <p className="mt-2 text-xs text-stone-500">{setLog.completedAt ?? 'Not completed'}</p>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{setLog.notes ?? 'No notes'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Progress check-ins"
                        description="Food, body, hydration, soreness, stress, and sleep quality."
                        section="progress"
                        paginator={tables.progress}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'progress_q', label: 'Search', type: 'search', placeholder: 'Notes' },
                            { key: 'progress_from', label: 'From', type: 'date' },
                            { key: 'progress_to', label: 'To', type: 'date' },
                        ]}
                        minWidth="min-w-[1220px]"
                        headers={['Date', 'Body', 'Food', 'Hydration', 'Feel', 'Sleep quality', 'Notes']}
                        empty="No progress check-ins found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.progress.data.map((row) => (
                                <tr key={row.id} className="align-top">
                                    <td className="px-5 py-4 text-sm text-stone-600">{row.loggedDate ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {row.weightKg ?? 'N/A'} kg · BF {row.bodyFatPercentage ?? 'N/A'}% · Waist {row.waistCm ?? 'N/A'} cm
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {row.caloriesConsumed ?? 'N/A'} kcal · P {row.proteinGrams ?? 'N/A'}g · C {row.carbsGrams ?? 'N/A'}g · F{' '}
                                        {row.fatGrams ?? 'N/A'}g
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {row.waterLiters ?? 'N/A'} L · Meals {row.mealsLoggedCount ?? 'N/A'}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        Energy {row.energyScore ?? 'N/A'} · Soreness {row.sorenessScore ?? 'N/A'} · Stress {row.stressScore ?? 'N/A'}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{row.sleepQualityScore ?? 'N/A'}/10</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{row.notes ?? 'No notes'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Wearables"
                        description="Device connection state and latest recovery snapshot."
                        section="devices"
                        paginator={tables.devices}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'devices_q', label: 'Search', type: 'search', placeholder: 'External user or error' },
                            { key: 'devices_status', label: 'Status', type: 'select', options: deviceStatusOptions },
                            { key: 'devices_provider', label: 'Provider', type: 'select', options: providerOptions },
                        ]}
                        minWidth="min-w-[1040px]"
                        headers={['Provider', 'Status', 'External user', 'Last sync', 'Metric date', 'Readiness', 'Sleep', 'Strain', 'Error']}
                        empty="No device connections found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.devices.data.map((device) => (
                                <tr key={device.id} className="align-top">
                                    <td className="px-5 py-4 font-medium text-stone-950">{device.provider}</td>
                                    <td className="px-5 py-4">
                                        <Badge variant={statusVariant(device.status)}>{humanize(device.status)}</Badge>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.externalUserId ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.lastSyncedAt ?? 'Never'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.latestMetricDate ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.readiness ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.sleepHours ?? 'N/A'} h</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.strain ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{device.lastErrorMessage ?? 'None'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Athlete files"
                        description="Documents, media, progress files, and admin-only records."
                        section="files"
                        paginator={tables.files}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'files_q', label: 'Search', type: 'search', placeholder: 'File or notes' },
                            { key: 'files_status', label: 'Status', type: 'select', options: fileOptions.statuses },
                            { key: 'files_category', label: 'Category', type: 'select', options: fileOptions.categories },
                            { key: 'files_visibility', label: 'Visibility', type: 'select', options: fileOptions.visibilities },
                        ]}
                        minWidth="min-w-[1240px]"
                        headers={['File', 'Category', 'Visibility', 'Status', 'Size', 'Uploaded', 'Notes', 'Actions']}
                        empty="No files uploaded for this athlete yet."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.files.data.map((file) => (
                                <tr key={file.id} className="align-top">
                                    <td className="px-5 py-4">
                                        <p className="font-medium text-stone-950">{file.displayName}</p>
                                        <p className="text-xs text-stone-500">{file.originalFilename}</p>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{humanize(file.category)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{humanize(file.visibility)}</td>
                                    <td className="px-5 py-4">
                                        <Badge variant={statusVariant(file.status)}>{humanize(file.status)}</Badge>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{formatSize(file.sizeBytes)}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {file.createdAt ?? 'N/A'} by {file.uploadedBy ?? 'System'}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{file.notes ?? 'No notes'}</td>
                                    <td className="px-5 py-4">
                                        <div className="flex flex-col gap-2">
                                            <FilePreviewDialog file={file} />
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
                    </TableSection>

                    <TableSection
                        title="Payments"
                        description="Billing events tied to this athlete."
                        section="payments"
                        paginator={tables.payments}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'payments_q', label: 'Search', type: 'search', placeholder: 'Reference, provider, notes' },
                            { key: 'payments_status', label: 'Status', type: 'select', options: paymentStatusOptions },
                            { key: 'payments_type', label: 'Type', type: 'select', options: paymentTypeOptions },
                            { key: 'payments_from', label: 'From', type: 'date' },
                            { key: 'payments_to', label: 'To', type: 'date' },
                        ]}
                        minWidth="min-w-[980px]"
                        headers={['Time', 'Type', 'Status', 'Provider', 'Amount', 'Reference', 'Notes']}
                        empty="No payment events found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.payments.data.map((payment) => (
                                <tr key={payment.id} className="align-top">
                                    <td className="px-5 py-4 text-sm text-stone-600">{payment.eventAt ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{humanize(payment.type)}</td>
                                    <td className="px-5 py-4">
                                        <Badge variant={statusVariant(payment.status)}>{humanize(payment.status)}</Badge>
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{payment.provider ?? 'Manual'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">
                                        {payment.amount ?? 'N/A'} {payment.currency ?? ''}
                                    </td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{payment.reference ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{payment.notes ?? 'No notes'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>

                    <TableSection
                        title="Messages"
                        description="Coach-athlete message history connected to roster assignments."
                        section="messages"
                        paginator={tables.messages}
                        query={query}
                        setField={setField}
                        controls={[
                            { key: 'messages_q', label: 'Search', type: 'search', placeholder: 'Message or sender' },
                            { key: 'messages_read', label: 'Read state', type: 'select', options: readOptions },
                        ]}
                        minWidth="min-w-[900px]"
                        headers={['Time', 'Coach', 'Sender', 'Recipient', 'Message', 'Read']}
                        empty="No messages found."
                    >
                        <tbody className="divide-y divide-stone-100">
                            {tables.messages.data.map((message) => (
                                <tr key={message.id} className="align-top">
                                    <td className="px-5 py-4 text-sm text-stone-600">{message.sentAt ?? 'N/A'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{message.coachName}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{message.senderName ?? 'System'}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{message.recipientName ?? profile.name}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{message.body}</td>
                                    <td className="px-5 py-4 text-sm text-stone-600">{message.readAt ?? 'Unread'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </TableSection>
                </section>
            </div>
        </AppLayout>
    );
}
