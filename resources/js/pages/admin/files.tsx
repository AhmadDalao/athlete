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
import { Archive, Download, Eye, Files, FileText, Search, UserRoundCheck } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin files', href: '/admin/files' },
];

interface Option {
    value: string;
    label: string;
}

interface FileRow {
    id: number;
    athlete: {
        id: number | null;
        name: string | null;
        email: string | null;
    };
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

interface FilePaginator {
    data: FileRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface AdminFilesProps {
    filters: {
        q: string | null;
        status: string | null;
        category: string | null;
        visibility: string | null;
        athlete_id: string | null;
    };
    summary: {
        total: number;
        active: number;
        archived: number;
        athletesWithFiles: number;
    };
    files: FilePaginator;
    athleteOptions: Option[];
    fileOptions: {
        categories: Option[];
        visibilities: Option[];
        statuses: Option[];
    };
}

interface FileUpdateFormData {
    athlete_id: string;
    display_name: string;
    category: string;
    visibility: string;
    status: string;
    notes: string;
}

function humanize(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
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
    if (status === 'active') {
        return 'default';
    }

    if (status === 'archived') {
        return 'destructive';
    }

    return 'outline';
}

function fileFilterPayload({
    q,
    status,
    category,
    visibility,
    athleteId,
}: {
    q: string;
    status: string;
    category: string;
    visibility: string;
    athleteId: string;
}) {
    return {
        q: q.trim() || undefined,
        status: status === 'all' ? undefined : status,
        category: category === 'all' ? undefined : category,
        visibility: visibility === 'all' ? undefined : visibility,
        athlete_id: athleteId === 'all' ? undefined : athleteId,
    };
}

function EditFileDialog({ file, athleteOptions, fileOptions }: { file: FileRow; athleteOptions: Option[]; fileOptions: AdminFilesProps['fileOptions'] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm<FileUpdateFormData>({
        athlete_id: file.athlete.id?.toString() ?? '',
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
                    Edit / move
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit file</DialogTitle>
                    <DialogDescription>Admins can move a file to another athlete, change category, change visibility, or archive it.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label>Athlete</Label>
                        <Select value={data.athlete_id} onValueChange={(value) => setData('athlete_id', value)}>
                            <SelectTrigger>
                                <SelectValue />
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
                    <div className="grid gap-2">
                        <Label htmlFor={`display-${file.id}`}>Display name</Label>
                        <Input id={`display-${file.id}`} value={data.display_name} onChange={(event) => setData('display_name', event.target.value)} />
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
                        <Label htmlFor={`notes-${file.id}`}>Notes</Label>
                        <Textarea id={`notes-${file.id}`} value={data.notes} onChange={(event) => setData('notes', event.target.value)} />
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

function FilePreviewDialog({ file }: { file: FileRow }) {
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

export default function AdminFiles({ filters, summary, files, athleteOptions, fileOptions }: AdminFilesProps) {
    const baseRoute = route('admin.files.index');
    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [category, setCategory] = useState(filters.category ?? 'all');
    const [visibility, setVisibility] = useState(filters.visibility ?? 'all');
    const [athleteId, setAthleteId] = useState(filters.athlete_id ?? 'all');
    const filterPayload = fileFilterPayload({ q, status, category, visibility, athleteId });

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(baseRoute, filterPayload, { preserveScroll: true, preserveState: true, replace: true });
    };

    useAutoFilter({ url: baseRoute, payload: filterPayload, only: ['filters', 'summary', 'files'] });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin files" />
            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow="Admin file library"
                    title="Athlete files should be searchable, movable, and exportable."
                    description="Central control for athlete documents, media, progress exports, and admin-only attachments. This keeps files out of random chats and inboxes."
                    badges={[`${summary.active} active`, `${summary.archived} archived`, `${summary.athletesWithFiles} athletes`]}
                    actions={
                        <>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                <Link href="/roster">
                                    <UserRoundCheck className="size-4" />
                                    Roster
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                <a href={route('admin.files.index', { ...filterPayload, export: 1 })}>
                                    <Download className="size-4" />
                                    Export CSV
                                </a>
                            </Button>
                        </>
                    }
                />

                <section className="grid gap-4 md:grid-cols-4">
                    <WorkspaceMetricCard title="Files" value={summary.total.toString()} note="Files matching the current filters." icon={Files} />
                    <WorkspaceMetricCard title="Active" value={summary.active.toString()} note="Visible in athlete or coach workflows." icon={FileText} />
                    <WorkspaceMetricCard title="Archived" value={summary.archived.toString()} note="Retained but lower priority." icon={Archive} />
                    <WorkspaceMetricCard title="Athletes" value={summary.athletesWithFiles.toString()} note="Athletes with at least one file." icon={UserRoundCheck} />
                </section>

                <WorkspacePanel title="Find files fast" description="Filter by athlete, category, visibility, and status before opening the table." contentClassName="space-y-4">
                    <form className="grid gap-3 xl:grid-cols-[1fr_220px_180px_220px_220px_auto_auto]" onSubmit={applyFilters}>
                        <div className="grid gap-2">
                            <Label htmlFor="file-q">Search</Label>
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                                <Input id="file-q" value={q} onChange={(event) => setQ(event.target.value)} className="pl-9" placeholder="File, athlete, notes" />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Athlete</Label>
                            <Select value={athleteId} onValueChange={setAthleteId}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All athletes</SelectItem>
                                    {athleteOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Status</Label>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    {fileOptions.statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select value={category} onValueChange={setCategory}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All categories</SelectItem>
                                    {fileOptions.categories.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Visibility</Label>
                            <Select value={visibility} onValueChange={setVisibility}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All visibility</SelectItem>
                                    {fileOptions.visibilities.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" className="w-full rounded-full bg-teal-700 text-white hover:bg-teal-800">
                                Apply
                            </Button>
                        </div>
                        <div className="flex items-end">
                            <Button asChild type="button" variant="outline" className="w-full rounded-full border-stone-300 bg-white">
                                <Link href={baseRoute}>Reset</Link>
                            </Button>
                        </div>
                    </form>
                </WorkspacePanel>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="File table"
                        title="One admin view for every athlete file."
                        description="Open the athlete profile, download the file, or move/archive it without guessing where it lives."
                    />
                    <WorkspacePanel title="Files" description={`${files.total} file record(s) match the current filters.`} contentClassName="space-y-4">
                        <WorkspaceTable minWidth="min-w-[1260px]">
                            <WorkspaceTableHeader labels={['Athlete', 'File', 'Category', 'Visibility', 'Status', 'Size', 'Uploaded', 'Notes', 'Actions']} />
                            {files.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No files found." colSpan={9} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {files.data.map((file) => (
                                        <tr key={file.id} className="align-top hover:bg-stone-50/80">
                                            <td className="px-5 py-4">
                                                <p className="font-semibold text-stone-950">{file.athlete.name ?? 'Unknown athlete'}</p>
                                                <p className="text-xs text-stone-500">{file.athlete.email}</p>
                                                {file.athlete.id && (
                                                    <Button asChild variant="link" size="sm" className="mt-1 h-auto p-0 text-stone-950">
                                                        <Link href={route('athletes.show', file.athlete.id)}>Open profile</Link>
                                                    </Button>
                                                )}
                                            </td>
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-stone-950">{file.displayName}</p>
                                                <p className="text-xs text-stone-500">{file.originalFilename}</p>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{humanize(file.category)}</td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{humanize(file.visibility)}</td>
                                            <td className="px-5 py-4"><Badge variant={statusVariant(file.status)}>{humanize(file.status)}</Badge></td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{formatSize(file.sizeBytes)}</td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{file.createdAt ?? 'N/A'} by {file.uploadedBy ?? 'System'}</td>
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
                                                    <EditFileDialog file={file} athleteOptions={athleteOptions} fileOptions={fileOptions} />
                                                    {file.status === 'active' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
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
                        {files.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-stone-200 pt-4">
                                <p className="text-sm text-stone-500">
                                    Page {files.current_page} of {files.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button asChild variant="outline" size="sm" disabled={!files.prev_page_url}>
                                        <Link href={files.prev_page_url ?? route('admin.files.index')} preserveScroll>
                                            Previous
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm" disabled={!files.next_page_url}>
                                        <Link href={files.next_page_url ?? route('admin.files.index')} preserveScroll>
                                            Next
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </WorkspacePanel>
                </section>
            </div>
        </AppLayout>
    );
}
