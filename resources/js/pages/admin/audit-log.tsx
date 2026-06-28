import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { WorkspaceMetricCard, WorkspacePanel, WorkspaceSectionHeading } from '@/components/workspace-primitives';
import { useAutoFilter } from '@/hooks/use-auto-filter';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, Download, Filter, RotateCcw, Search, ShieldCheck, UserCheck } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit log',
        href: '/admin/audit-log',
    },
];

interface AuditRow {
    id: number;
    time: string | null;
    actorName: string;
    actorEmail: string | null;
    action: string;
    entityType: string | null;
    entityId: number | null;
    summary: string;
    ipAddress: string | null;
}

interface AuditPaginator {
    data: AuditRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface AuditLogProps {
    filters: {
        q: string | null;
        action: string | null;
        entity: string | null;
        date_from: string | null;
        date_to: string | null;
    };
    summary: {
        totalEvents: number;
        filteredEvents: number;
        eventsToday: number;
        uniqueActors: number;
    };
    actions: string[];
    entities: string[];
    logs: AuditPaginator;
}

function humanize(value: string | null) {
    if (!value) {
        return 'N/A';
    }

    return value.replace(/[._-]/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function auditFilterPayload({
    q,
    action,
    entity,
    dateFrom,
    dateTo,
}: {
    q: string;
    action: string;
    entity: string;
    dateFrom: string;
    dateTo: string;
}) {
    return {
        q: q.trim() || undefined,
        action: action || undefined,
        entity: entity || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    };
}

function exportHref(filters: Record<string, string | number | boolean | null | undefined>) {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value) {
            params.set(key, value.toString());
        }
    });

    params.set('export', '1');

    return `${route('admin.audit-log.index')}?${params.toString()}`;
}

export default function AuditLog({ filters, summary, actions, entities, logs }: AuditLogProps) {
    const baseRoute = route('admin.audit-log.index');
    const [q, setQ] = useState(filters.q ?? '');
    const [action, setAction] = useState(filters.action ?? '');
    const [entity, setEntity] = useState(filters.entity ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const filterPayload = auditFilterPayload({ q, action, entity, dateFrom, dateTo });

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(baseRoute, filterPayload, { preserveScroll: true, preserveState: true, replace: true });
    };

    useAutoFilter({ url: baseRoute, payload: filterPayload, only: ['filters', 'summary', 'logs'] });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Log" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4 border-b border-stone-200 pb-5">
                    <div>
                        <p className="text-sm font-semibold text-stone-500">Admin accountability</p>
                        <h1 className="font-['Space_Grotesk'] text-4xl font-bold tracking-[-0.05em] text-stone-950">Audit Log</h1>
                    </div>
                    <Button asChild variant="outline" className="rounded-xl border-stone-300 bg-white">
                        <a href={exportHref(filterPayload)}>
                            <Download className="size-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <section className="grid gap-4 md:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Total events"
                        value={summary.totalEvents.toString()}
                        note="All recorded platform actions."
                        icon={Activity}
                    />
                    <WorkspaceMetricCard
                        title="Current result"
                        value={summary.filteredEvents.toString()}
                        note="Events matching the active filters."
                        icon={Search}
                    />
                    <WorkspaceMetricCard
                        title="Today"
                        value={summary.eventsToday.toString()}
                        note="Actions recorded since midnight."
                        icon={ShieldCheck}
                    />
                    <WorkspaceMetricCard
                        title="Actors"
                        value={summary.uniqueActors.toString()}
                        note="Users who changed something."
                        icon={UserCheck}
                    />
                </section>

                <form onSubmit={applyFilters} className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                    <div className="grid gap-3 lg:grid-cols-[1.3fr_1fr_1fr_0.9fr_0.9fr_auto_auto] lg:items-end">
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Search
                            <Input value={q} onChange={(event) => setQ(event.target.value)} placeholder="Summary, action, user, IP" />
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Action
                            <select
                                value={action}
                                onChange={(event) => setAction(event.target.value)}
                                className="border-input h-10 rounded-md border bg-white px-3 text-sm"
                            >
                                <option value="">All actions</option>
                                {actions.map((action) => (
                                    <option key={action} value={action}>
                                        {humanize(action)}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Entity
                            <select
                                value={entity}
                                onChange={(event) => setEntity(event.target.value)}
                                className="border-input h-10 rounded-md border bg-white px-3 text-sm"
                            >
                                <option value="">All entities</option>
                                {entities.map((entity) => (
                                    <option key={entity} value={entity}>
                                        {entity}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            From
                            <Input type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} />
                        </label>
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            To
                            <Input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} />
                        </label>
                        <Button type="submit" className="rounded-xl bg-amber-500 text-white hover:bg-amber-600">
                            <Filter className="size-4" />
                            Filter
                        </Button>
                        <Button asChild variant="outline" className="rounded-xl border-stone-300 bg-white">
                            <Link href={baseRoute}>
                                <RotateCcw className="size-4" />
                                Reset
                            </Link>
                        </Button>
                    </div>
                </form>

                <WorkspacePanel
                    title={`System Activity ${logs.total}`}
                    description="Admin actions, account changes, training assignments, billing edits, progress logs, and API key events."
                    contentClassName="space-y-4"
                >
                    <WorkspaceSectionHeading
                        eyebrow="Log"
                        title="Recorded platform actions."
                        description="This table is intentionally boring: who changed what, when, and from where."
                    />

                    <div className="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
                        <table className="w-full min-w-[980px] text-left text-sm">
                            <thead className="bg-stone-50 text-xs font-semibold tracking-[0.16em] text-stone-500 uppercase">
                                <tr>
                                    <th className="px-4 py-3">Time</th>
                                    <th className="px-4 py-3">User</th>
                                    <th className="px-4 py-3">Action</th>
                                    <th className="px-4 py-3">Entity</th>
                                    <th className="px-4 py-3">Summary</th>
                                    <th className="px-4 py-3">IP</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100">
                                {logs.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-stone-500">
                                            No audit activity found.
                                        </td>
                                    </tr>
                                ) : (
                                    logs.data.map((log) => (
                                        <tr key={log.id} className="align-top">
                                            <td className="px-4 py-4 text-stone-600">{log.time ?? 'N/A'}</td>
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-stone-950">{log.actorName}</p>
                                                {log.actorEmail && <p className="text-xs text-stone-500">{log.actorEmail}</p>}
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">{humanize(log.action)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {log.entityType ?? 'System'}
                                                {log.entityId ? ` #${log.entityId}` : ''}
                                            </td>
                                            <td className="max-w-md px-4 py-4 leading-6 text-stone-700">{log.summary}</td>
                                            <td className="px-4 py-4 text-stone-500">{log.ipAddress ?? 'N/A'}</td>
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
