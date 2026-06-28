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
import { ArrowLeft, Download, MailCheck, MailPlus, RefreshCcw, Search, Send, XCircle } from 'lucide-react';
import { type FormEvent, useEffect, useRef, useState } from 'react';

interface Option {
    value: string;
    label: string;
}

interface InvitationRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    goal: string | null;
    notes: string | null;
    status: string;
    expiresAt: string | null;
    acceptedAt: string | null;
    cancelledAt: string | null;
    createdAt: string | null;
    coach: {
        id: number | null;
        name: string | null;
        email: string | null;
    };
    invitedBy: {
        id: number | null;
        name: string | null;
    };
    acceptedUser: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface InvitationPaginator {
    data: InvitationRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface InvitationPageProps {
    adminMode: boolean;
    filters: {
        q: string | null;
        status: string | null;
        coach_id: string | null;
    };
    summary: {
        total: number;
        pending: number;
        accepted: number;
        cancelled: number;
        expired: number;
    };
    invitations: InvitationPaginator;
    coachOptions: Option[];
    statusOptions: string[];
}

interface InviteFormData {
    coach_id: string;
    name: string;
    email: string;
    phone: string;
    goal: string;
    notes: string;
}

function humanize(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'accepted') {
        return 'default';
    }

    if (status === 'pending') {
        return 'secondary';
    }

    if (['expired', 'cancelled'].includes(status)) {
        return 'destructive';
    }

    return 'outline';
}

function invitationFilterPayload({ q, status, coachId }: { q: string; status: string; coachId: string }) {
    return {
        q: q.trim() || undefined,
        status: status === 'all' ? undefined : status,
        coach_id: coachId === 'all' ? undefined : coachId,
    };
}

function InviteDialog({ adminMode, coachOptions }: { adminMode: boolean; coachOptions: Option[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<InviteFormData>({
        coach_id: coachOptions[0]?.value ?? '',
        name: '',
        email: '',
        phone: '',
        goal: '',
        notes: '',
    });
    const disabled = adminMode && coachOptions.length === 0;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('roster.invitations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset('name', 'email', 'phone', 'goal', 'notes');
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button disabled={disabled} size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                    <MailPlus className="size-4" />
                    Invite athlete
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Invite athlete</DialogTitle>
                    <DialogDescription>
                        Send an email invitation, let the athlete finish account setup, then automatically attach them to the coach roster.
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    {adminMode && (
                        <div className="grid gap-2">
                            <Label htmlFor="invite-coach">Coach</Label>
                            <Select value={data.coach_id} onValueChange={(value) => setData('coach_id', value)}>
                                <SelectTrigger id="invite-coach">
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
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="invite-name">Athlete name</Label>
                            <Input id="invite-name" value={data.name} onChange={(event) => setData('name', event.target.value)} />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="invite-email">Email</Label>
                            <Input id="invite-email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} />
                            <InputError message={errors.email} />
                        </div>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="invite-phone">Phone</Label>
                            <Input id="invite-phone" value={data.phone} onChange={(event) => setData('phone', event.target.value)} />
                            <InputError message={errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="invite-goal">Goal</Label>
                            <Input id="invite-goal" value={data.goal} onChange={(event) => setData('goal', event.target.value)} />
                            <InputError message={errors.goal} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="invite-notes">Internal notes</Label>
                        <Textarea id="invite-notes" value={data.notes} onChange={(event) => setData('notes', event.target.value)} />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing || disabled}>
                            <Send className="size-4" />
                            Send invitation
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function InvitationIndex({ adminMode, filters, summary, invitations, coachOptions, statusOptions }: InvitationPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roster', href: '/roster' },
        { title: adminMode ? 'Admin invitations' : 'Invitations', href: adminMode ? '/admin/invitations' : '/roster/invites' },
    ];
    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [coachId, setCoachId] = useState(filters.coach_id ?? 'all');
    const baseRoute = adminMode ? route('admin.invitations.index') : route('roster.invitations.index');
    const didHydrate = useRef(false);

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(baseRoute, invitationFilterPayload({ q, status, coachId }), { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (!didHydrate.current) {
            didHydrate.current = true;

            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(baseRoute, invitationFilterPayload({ q, status, coachId }), {
                only: ['filters', 'summary', 'invitations'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 150);

        return () => window.clearTimeout(timeout);
    }, [baseRoute, coachId, q, status]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={adminMode ? 'Admin invitations' : 'Athlete invitations'} />
            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <WorkspaceHero
                    eyebrow={adminMode ? 'Admin invitation control' : 'Coach invite pipeline'}
                    title="Athlete invitations should be visible, resendable, and accountable."
                    description="Invite athletes by email, track who accepted, cancel stale links, and keep the onboarding trail out of random inbox archaeology."
                    badges={[`${summary.pending} pending`, `${summary.accepted} accepted`, `${summary.expired} expired`]}
                    actions={
                        <>
                            <InviteDialog adminMode={adminMode} coachOptions={coachOptions} />
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                <Link href="/roster">
                                    <ArrowLeft className="size-4" />
                                    Roster
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                <a href={route(adminMode ? 'admin.invitations.index' : 'roster.invitations.index', { ...invitationFilterPayload({ q, status, coachId }), export: 1 })}>
                                    <Download className="size-4" />
                                    Export CSV
                                </a>
                            </Button>
                        </>
                    }
                />

                <section className="grid gap-4 md:grid-cols-4">
                    <WorkspaceMetricCard title="Total invites" value={summary.total.toString()} note="All invitation records in this view." icon={MailPlus} />
                    <WorkspaceMetricCard title="Pending" value={summary.pending.toString()} note="Waiting for athlete action." icon={RefreshCcw} />
                    <WorkspaceMetricCard title="Accepted" value={summary.accepted.toString()} note="Converted into roster relationships." icon={MailCheck} />
                    <WorkspaceMetricCard title="Closed" value={(summary.cancelled + summary.expired).toString()} note="Cancelled or expired links." icon={XCircle} />
                </section>

                <WorkspacePanel title="Find invitations fast" description="Filter before the list gets ugly." contentClassName="space-y-4">
                    <form className="grid gap-3 lg:grid-cols-[1fr_220px_220px_auto_auto]" onSubmit={applyFilters}>
                        <div className="grid gap-2">
                            <Label htmlFor="invite-q">Search</Label>
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                                <Input id="invite-q" value={q} onChange={(event) => setQ(event.target.value)} className="pl-9" placeholder="Name, email, phone, goal" />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Status</Label>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    {statusOptions.map((option) => (
                                        <SelectItem key={option} value={option}>
                                            {humanize(option)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        {adminMode && (
                            <div className="grid gap-2">
                                <Label>Coach</Label>
                                <Select value={coachId} onValueChange={setCoachId}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All coaches</SelectItem>
                                        {coachOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
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
                        eyebrow="Invitation table"
                        title="Every athlete invite in rows, not mystery cards."
                        description="This is the onboarding audit trail: who invited, who accepted, and what needs resend or cancellation."
                    />
                    <WorkspacePanel title="Invitations" description={`${invitations.total} invite record(s) match the current filters.`} contentClassName="space-y-4">
                        <WorkspaceTable minWidth="min-w-[1180px]">
                            <WorkspaceTableHeader labels={['Athlete', 'Coach', 'Status', 'Goal', 'Dates', 'Accepted user', 'Actions']} />
                            {invitations.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No invitations found." colSpan={7} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {invitations.data.map((invitation) => (
                                        <tr key={invitation.id} className="align-top hover:bg-stone-50/80">
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-stone-950">{invitation.name}</p>
                                                <p className="mt-1 text-xs text-stone-500">{invitation.email}</p>
                                                <p className="mt-1 text-xs text-stone-500">{invitation.phone ?? 'No phone'}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{invitation.coach.name ?? 'No coach'}</p>
                                                <p className="mt-1 text-xs text-stone-500">{invitation.coach.email}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={statusVariant(invitation.status)}>{humanize(invitation.status)}</Badge>
                                                <p className="mt-2 text-xs text-stone-500">By {invitation.invitedBy.name ?? 'System'}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="max-w-[16rem] text-sm text-stone-700">{invitation.goal ?? 'No goal set'}</p>
                                                <p className="mt-2 line-clamp-2 max-w-[16rem] text-xs text-stone-500">{invitation.notes ?? 'No notes'}</p>
                                            </td>
                                            <td className="px-4 py-4 text-xs text-stone-600">
                                                <p>Created {invitation.createdAt ?? 'N/A'}</p>
                                                <p className="mt-1">Expires {invitation.expiresAt ?? 'N/A'}</p>
                                                {invitation.cancelledAt && <p className="mt-1">Cancelled {invitation.cancelledAt}</p>}
                                            </td>
                                            <td className="px-4 py-4">
                                                {invitation.acceptedUser ? (
                                                    <>
                                                        <p className="font-medium text-stone-950">{invitation.acceptedUser.name}</p>
                                                        <Button asChild variant="link" className="h-auto p-0 text-stone-950">
                                                            <Link href={route('athletes.show', invitation.acceptedUser.id)}>Open profile</Link>
                                                        </Button>
                                                    </>
                                                ) : (
                                                    <span className="text-xs text-stone-500">Not accepted</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-col gap-2">
                                                    {invitation.status !== 'accepted' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => router.post(route('roster.invitations.resend', invitation.id), {}, { preserveScroll: true })}
                                                        >
                                                            Resend
                                                        </Button>
                                                    )}
                                                    {invitation.status === 'pending' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            className="border-red-200 text-red-700 hover:bg-red-50"
                                                            onClick={() => router.post(route('roster.invitations.cancel', invitation.id), {}, { preserveScroll: true })}
                                                        >
                                                            Cancel
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                        {invitations.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-stone-200 pt-4">
                                <p className="text-sm text-stone-500">
                                    Page {invitations.current_page} of {invitations.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button asChild variant="outline" size="sm" disabled={!invitations.prev_page_url}>
                                        <Link href={invitations.prev_page_url ?? baseRoute} preserveScroll>
                                            Previous
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm" disabled={!invitations.next_page_url}>
                                        <Link href={invitations.next_page_url ?? baseRoute} preserveScroll>
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
