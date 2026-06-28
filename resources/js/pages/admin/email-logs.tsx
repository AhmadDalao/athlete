import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { WorkspaceMetricCard, WorkspacePanel, WorkspaceSectionHeading } from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bell, Download, Filter, MailCheck, MailWarning, RotateCcw, Send } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Email logs',
        href: '/admin/email-logs',
    },
];

interface EmailRow {
    id: number;
    time: string | null;
    status: string;
    type: string;
    recipient: string | null;
    subject: string | null;
    source: string | null;
    mailer: string | null;
    messageId: string | null;
    error: string | null;
    attemptedAt: string | null;
    sentAt: string | null;
}

interface EmailPaginator {
    data: EmailRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface EmailLogsProps {
    filters: {
        q: string | null;
        status: string | null;
        type: string | null;
        date_from: string | null;
        date_to: string | null;
    };
    summary: {
        totalAttempts: number;
        sent: number;
        attempting: number;
        failed: number;
    };
    statuses: string[];
    types: string[];
    logs: EmailPaginator;
}

function humanize(value: string | null) {
    if (!value) {
        return 'N/A';
    }

    return value.replace(/[._-]/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function badgeVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'failed') {
        return 'destructive';
    }

    if (status === 'sent') {
        return 'default';
    }

    if (status === 'attempting') {
        return 'secondary';
    }

    return 'outline';
}

function exportHref(filters: EmailLogsProps['filters']) {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    params.set('export', '1');

    return `${route('admin.email-logs.index')}?${params.toString()}`;
}

export default function EmailLogs({ filters, summary, statuses, types, logs }: EmailLogsProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Email Logs" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4 border-b border-stone-200 pb-5">
                    <div>
                        <p className="text-sm font-semibold text-stone-500">Mailer delivery trail</p>
                        <h1 className="font-['Space_Grotesk'] text-4xl font-bold tracking-[-0.05em] text-stone-950">Email Logs</h1>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="rounded-xl border-stone-300 bg-white">
                            <Link href={route('admin.system-settings.index')}>Email settings</Link>
                        </Button>
                        <Button asChild variant="outline" className="rounded-xl border-stone-300 bg-white">
                            <a href={exportHref(filters)}>
                                <Download className="size-4" />
                                Export CSV
                            </a>
                        </Button>
                    </div>
                </div>

                <section className="grid gap-4 md:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Total attempts"
                        value={summary.totalAttempts.toString()}
                        note="Emails recorded by Laravel mail events."
                        icon={Bell}
                    />
                    <WorkspaceMetricCard title="Sent" value={summary.sent.toString()} note="Accepted by the configured mailer." icon={MailCheck} />
                    <WorkspaceMetricCard
                        title="Attempting"
                        value={summary.attempting.toString()}
                        note="Started but not confirmed sent yet."
                        icon={Send}
                    />
                    <WorkspaceMetricCard title="Failed" value={summary.failed.toString()} note="Recorded workflow failures." icon={MailWarning} />
                </section>

                <form action={route('admin.email-logs.index')} method="get" className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                    <div className="grid gap-3 lg:grid-cols-[1.3fr_1fr_1fr_0.9fr_0.9fr_auto_auto] lg:items-end">
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Search
                            <Input name="q" defaultValue={filters.q ?? ''} placeholder="Recipient, subject, type, error" />
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Status
                            <select
                                name="status"
                                defaultValue={filters.status ?? ''}
                                className="border-input h-10 rounded-md border bg-white px-3 text-sm"
                            >
                                <option value="">All statuses</option>
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {humanize(status)}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Email type
                            <select
                                name="type"
                                defaultValue={filters.type ?? ''}
                                className="border-input h-10 rounded-md border bg-white px-3 text-sm"
                            >
                                <option value="">All types</option>
                                {types.map((type) => (
                                    <option key={type} value={type}>
                                        {type}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            From
                            <Input type="date" name="date_from" defaultValue={filters.date_from ?? ''} />
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            To
                            <Input type="date" name="date_to" defaultValue={filters.date_to ?? ''} />
                        </label>
                        <Button type="submit" className="rounded-xl bg-amber-500 text-white hover:bg-amber-600">
                            <Filter className="size-4" />
                            Filter
                        </Button>
                        <Button asChild variant="outline" className="rounded-xl border-stone-300 bg-white">
                            <Link href={route('admin.email-logs.index')}>
                                <RotateCcw className="size-4" />
                                Reset
                            </Link>
                        </Button>
                    </div>
                </form>

                <WorkspacePanel
                    title={`Delivery Attempts ${logs.total}`}
                    description="Password reset, verification, support, and workflow email attempts are recorded here."
                    contentClassName="space-y-4"
                >
                    <WorkspaceSectionHeading
                        eyebrow="Log"
                        title="Mailer delivery trail."
                        description="Use this before blaming users for not receiving a reset or onboarding email."
                    />

                    <div className="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
                        <table className="w-full min-w-[980px] text-left text-sm">
                            <thead className="bg-stone-50 text-xs font-semibold tracking-[0.16em] text-stone-500 uppercase">
                                <tr>
                                    <th className="px-4 py-3">Time</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Type</th>
                                    <th className="px-4 py-3">Recipient</th>
                                    <th className="px-4 py-3">Subject</th>
                                    <th className="px-4 py-3">Source</th>
                                    <th className="px-4 py-3">Error</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100">
                                {logs.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-8 text-center text-stone-500">
                                            No email delivery logs found.
                                        </td>
                                    </tr>
                                ) : (
                                    logs.data.map((log) => (
                                        <tr key={log.id} className="align-top">
                                            <td className="px-4 py-4 text-stone-600">{log.time ?? 'N/A'}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariant(log.status)}>{humanize(log.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-stone-700">{log.type}</td>
                                            <td className="px-4 py-4 text-stone-600">{log.recipient ?? 'N/A'}</td>
                                            <td className="max-w-xs px-4 py-4 leading-6 text-stone-700">{log.subject ?? 'No subject'}</td>
                                            <td className="px-4 py-4 text-stone-500">{log.source ?? log.mailer ?? 'N/A'}</td>
                                            <td className="max-w-xs px-4 py-4 leading-6 text-red-700">{log.error ?? '-'}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-stone-500">
                        <span>
                            Page {logs.current_page} of {logs.last_page}
                        </span>
                        <div className="flex gap-2">
                            <Button asChild variant="outline" disabled={!logs.prev_page_url}>
                                {logs.prev_page_url ? <Link href={logs.prev_page_url}>Previous</Link> : <span>Previous</span>}
                            </Button>
                            <Button asChild variant="outline" disabled={!logs.next_page_url}>
                                {logs.next_page_url ? <Link href={logs.next_page_url}>Next</Link> : <span>Next</span>}
                            </Button>
                        </div>
                    </div>
                </WorkspacePanel>
            </div>
        </AppLayout>
    );
}
