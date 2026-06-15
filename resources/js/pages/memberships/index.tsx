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
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ArrowRight, CreditCard, ReceiptText, Settings2, ShieldCheck, Timer } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Memberships',
        href: '/memberships',
    },
];

const statusFilters = [
    { label: 'All', value: null },
    { label: 'Active', value: 'active' },
    { label: 'Trialing', value: 'trialing' },
    { label: 'Grace', value: 'grace' },
    { label: 'Past due', value: 'past_due' },
    { label: 'Cancelled', value: 'cancelled' },
    { label: 'Expired', value: 'expired' },
] as const;

interface PaymentEventRow {
    id: number;
    eventType: string;
    status: string;
    provider: string | null;
    reference: string | null;
    amount: number | null;
    currency: string;
    eventAt: string | null;
    notes: string | null;
    createdBy: string | null;
}

interface MembershipRow {
    id: number;
    userName: string;
    userEmail: string;
    userPhone: string | null;
    userRole: string | null;
    planName: string;
    status: string;
    daysRemaining: number | null;
    renewsAt: string | null;
    endsAt: string | null;
    effectiveEndsAt: string | null;
    graceEndsAt: string | null;
    cancelledAt: string | null;
    autoRenew: boolean;
    price: number;
    currency: string;
    notes: string | null;
    paymentEvents: PaymentEventRow[];
}

interface MembershipPaginator {
    data: MembershipRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface Option {
    value: string;
    label: string;
}

interface MembershipPageProps {
    viewerRole: string | null;
    canManageMemberships: boolean;
    scopeLabel: string;
    filters: {
        status: string | null;
    };
    summary: {
        totalMemberships: number;
        currentAccess: number;
        attentionRequired: number;
        renewingThisWeek: number;
        autoRenewEnabled: number;
        projectedMonthlyRevenue: number;
        paymentVolumeThisMonth: number;
        failedPaymentsThisMonth: number;
    };
    memberships: MembershipPaginator;
    auditCommand: string;
    paymentEventTypes: Option[];
    paymentEventStatuses: Option[];
}

interface MembershipUpdateFormData {
    status: string;
    auto_renew: '1' | '0';
    renews_at: string;
    ends_at: string;
    grace_ends_at: string;
    extension_days: string;
    notes: string;
}

interface PaymentEventFormData {
    event_type: string;
    status: string;
    provider: string;
    reference: string;
    amount: string;
    currency: string;
    event_at: string;
    notes: string;
}

function formatCurrency(value: number, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
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

function formatDateTime(value: string | null) {
    if (!value) {
        return 'No timestamp';
    }

    return value.replace('T', ' ');
}

function formatDateForInput(value: string | null) {
    return value ?? '';
}

function formatDateTimeForInput() {
    const now = new Date();
    const offset = now.getTimezoneOffset();
    const local = new Date(now.getTime() - offset * 60_000);

    return local.toISOString().slice(0, 16);
}

function humanizeStatus(status: string) {
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function badgeVariantForStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['past_due', 'cancelled', 'expired', 'failed'].includes(status)) {
        return 'destructive';
    }

    if (['grace', 'pending'].includes(status)) {
        return 'secondary';
    }

    if (['trialing', 'active', 'succeeded', 'info'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

function MetricCard({
    title,
    value,
    note,
    icon: Icon,
}: {
    title: string;
    value: string;
    note: string;
    icon: typeof CreditCard;
}) {
    return (
        <Card className="border-sidebar-border/70">
            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                    <CardDescription>{title}</CardDescription>
                    <CardTitle className="mt-3 text-3xl font-semibold tracking-tight">{value}</CardTitle>
                </div>
                <div className="rounded-full bg-primary/10 p-2 text-primary">
                    <Icon className="size-4" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-sm leading-6 text-muted-foreground">{note}</p>
            </CardContent>
        </Card>
    );
}

function MembershipSettingsDialog({ membership }: { membership: MembershipRow }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm<MembershipUpdateFormData>({
        status: membership.status,
        auto_renew: membership.autoRenew ? '1' : '0',
        renews_at: formatDateForInput(membership.renewsAt),
        ends_at: formatDateForInput(membership.endsAt),
        grace_ends_at: formatDateForInput(membership.graceEndsAt),
        extension_days: '',
        notes: membership.notes ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(route('memberships.update', membership.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset('extension_days');
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Settings2 className="mr-2 size-4" />
                    Update membership
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Update {membership.userName}</DialogTitle>
                    <DialogDescription>Adjust status, renewal dates, and notes without pretending spreadsheets are a system.</DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`membership-status-${membership.id}`}>Status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger id={`membership-status-${membership.id}`}>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statusFilters
                                        .filter((option) => option.value)
                                        .map((option) => (
                                            <SelectItem key={option.value} value={option.value!}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`membership-auto-renew-${membership.id}`}>Auto renew</Label>
                            <Select value={data.auto_renew} onValueChange={(value: '1' | '0') => setData('auto_renew', value)}>
                                <SelectTrigger id={`membership-auto-renew-${membership.id}`}>
                                    <SelectValue placeholder="Auto renew" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Enabled</SelectItem>
                                    <SelectItem value="0">Disabled</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.auto_renew} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor={`membership-renews-at-${membership.id}`}>Renews on</Label>
                            <Input
                                id={`membership-renews-at-${membership.id}`}
                                type="date"
                                value={data.renews_at}
                                onChange={(event) => setData('renews_at', event.target.value)}
                            />
                            <InputError message={errors.renews_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`membership-ends-at-${membership.id}`}>Access ends</Label>
                            <Input
                                id={`membership-ends-at-${membership.id}`}
                                type="date"
                                value={data.ends_at}
                                onChange={(event) => setData('ends_at', event.target.value)}
                            />
                            <InputError message={errors.ends_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`membership-grace-ends-at-${membership.id}`}>Grace ends</Label>
                            <Input
                                id={`membership-grace-ends-at-${membership.id}`}
                                type="date"
                                value={data.grace_ends_at}
                                onChange={(event) => setData('grace_ends_at', event.target.value)}
                            />
                            <InputError message={errors.grace_ends_at} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-[0.32fr_0.68fr]">
                        <div className="grid gap-2">
                            <Label htmlFor={`membership-extension-days-${membership.id}`}>Extend by days</Label>
                            <Input
                                id={`membership-extension-days-${membership.id}`}
                                type="number"
                                min={0}
                                max={365}
                                value={data.extension_days}
                                onChange={(event) => setData('extension_days', event.target.value)}
                            />
                            <InputError message={errors.extension_days} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`membership-notes-${membership.id}`}>Operator notes</Label>
                            <Textarea
                                id={`membership-notes-${membership.id}`}
                                value={data.notes}
                                onChange={(event) => setData('notes', event.target.value)}
                                placeholder="Capture cancellation context, payment promises, or why this changed."
                            />
                            <InputError message={errors.notes} />
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button disabled={processing} asChild>
                            <button type="submit">Save membership</button>
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function PaymentEventDialog({
    membership,
    paymentEventTypes,
    paymentEventStatuses,
}: {
    membership: MembershipRow;
    paymentEventTypes: Option[];
    paymentEventStatuses: Option[];
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<PaymentEventFormData>({
        event_type: paymentEventTypes[0]?.value ?? 'charge',
        status: paymentEventStatuses[0]?.value ?? 'pending',
        provider: 'manual',
        reference: '',
        amount: membership.price.toString(),
        currency: membership.currency,
        event_at: formatDateTimeForInput(),
        notes: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('memberships.events.store', membership.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <ReceiptText className="mr-2 size-4" />
                    Record event
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Record payment event</DialogTitle>
                    <DialogDescription>Track the billing reality in the app instead of losing it in random side chats.</DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`payment-event-type-${membership.id}`}>Event type</Label>
                            <Select value={data.event_type} onValueChange={(value) => setData('event_type', value)}>
                                <SelectTrigger id={`payment-event-type-${membership.id}`}>
                                    <SelectValue placeholder="Select event type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {paymentEventTypes.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.event_type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`payment-event-status-${membership.id}`}>Event status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger id={`payment-event-status-${membership.id}`}>
                                    <SelectValue placeholder="Select event status" />
                                </SelectTrigger>
                                <SelectContent>
                                    {paymentEventStatuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`payment-provider-${membership.id}`}>Provider</Label>
                            <Input
                                id={`payment-provider-${membership.id}`}
                                value={data.provider}
                                onChange={(event) => setData('provider', event.target.value)}
                                placeholder="manual, stripe, paypal"
                            />
                            <InputError message={errors.provider} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`payment-reference-${membership.id}`}>Reference</Label>
                            <Input
                                id={`payment-reference-${membership.id}`}
                                value={data.reference}
                                onChange={(event) => setData('reference', event.target.value)}
                                placeholder="INV-2048 or gateway ID"
                            />
                            <InputError message={errors.reference} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-[0.38fr_0.22fr_0.4fr]">
                        <div className="grid gap-2">
                            <Label htmlFor={`payment-amount-${membership.id}`}>Amount</Label>
                            <Input
                                id={`payment-amount-${membership.id}`}
                                type="number"
                                min={0}
                                step="0.01"
                                value={data.amount}
                                onChange={(event) => setData('amount', event.target.value)}
                            />
                            <InputError message={errors.amount} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`payment-currency-${membership.id}`}>Currency</Label>
                            <Input
                                id={`payment-currency-${membership.id}`}
                                value={data.currency}
                                maxLength={3}
                                onChange={(event) => setData('currency', event.target.value.toUpperCase())}
                            />
                            <InputError message={errors.currency} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`payment-event-at-${membership.id}`}>Event time</Label>
                            <Input
                                id={`payment-event-at-${membership.id}`}
                                type="datetime-local"
                                value={data.event_at}
                                onChange={(event) => setData('event_at', event.target.value)}
                            />
                            <InputError message={errors.event_at} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`payment-notes-${membership.id}`}>Notes</Label>
                        <Textarea
                            id={`payment-notes-${membership.id}`}
                            value={data.notes}
                            onChange={(event) => setData('notes', event.target.value)}
                            placeholder="Explain what happened. Future-you will not remember."
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end">
                        <Button disabled={processing} asChild>
                            <button type="submit">Save event</button>
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function MembershipIndex({
    viewerRole,
    canManageMemberships,
    scopeLabel,
    filters,
    summary,
    memberships,
    auditCommand,
    paymentEventTypes,
    paymentEventStatuses,
}: MembershipPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Memberships" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Card className="border-sidebar-border/70 bg-linear-to-br from-background via-background to-muted/40">
                    <CardHeader>
                        <CardTitle className="text-3xl">Membership control</CardTitle>
                        <CardDescription className="max-w-3xl leading-6">
                            {scopeLabel}. This page is the billing reality check: status, renewals, money movement, and who is about to become a support ticket.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 xl:grid-cols-[1.45fr_0.55fr]">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                title="Tracked memberships"
                                value={summary.totalMemberships.toString()}
                                note={`${memberships.total} record(s) match the current view.`}
                                icon={CreditCard}
                            />
                            <MetricCard
                                title="Current access"
                                value={summary.currentAccess.toString()}
                                note="Includes active, trialing, grace, past-due, and scheduled-to-end access."
                                icon={ShieldCheck}
                            />
                            <MetricCard
                                title="Attention required"
                                value={summary.attentionRequired.toString()}
                                note="Grace, past due, and cancelled memberships should not sit ignored."
                                icon={AlertTriangle}
                            />
                            <MetricCard
                                title="Renewing this week"
                                value={summary.renewingThisWeek.toString()}
                                note="Seven-day renewal horizon for manual follow-up."
                                icon={Timer}
                            />
                        </div>

                        <div className="space-y-4 rounded-2xl border border-sidebar-border/70 bg-muted/30 p-5">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    {viewerRole === 'athlete' ? 'Your membership value' : 'Projected monthly revenue'}
                                </p>
                                <p className="mt-3 text-3xl font-semibold tracking-tight">
                                    {formatCurrency(summary.projectedMonthlyRevenue)}
                                </p>
                                <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                    {viewerRole === 'athlete'
                                        ? 'This reflects the current monthly value of your active access.'
                                        : 'Calculated from current memberships and normalized plan intervals.'}
                                </p>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-1">
                                <div className="rounded-xl border border-sidebar-border/70 bg-background/80 p-3">
                                    <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Collected this month</p>
                                    <p className="mt-2 text-lg font-semibold">{formatCurrency(summary.paymentVolumeThisMonth)}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">{summary.failedPaymentsThisMonth} failed payment events this month.</p>
                                </div>
                                <div className="rounded-xl border border-sidebar-border/70 bg-background/80 p-3">
                                    <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Auto renew</p>
                                    <p className="mt-2 text-lg font-semibold">{summary.autoRenewEnabled}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">Memberships currently set to renew automatically.</p>
                                </div>
                            </div>

                            <div className="rounded-xl border border-dashed border-sidebar-border/70 bg-background/80 p-3">
                                <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">Audit command</p>
                                <code className="mt-2 block text-sm">{auditCommand}</code>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <section className="space-y-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="text-xl font-semibold tracking-tight">Membership queue</h2>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Filter the list by status and keep the next renewal mess small.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {statusFilters.map((filter) => (
                                <Link
                                    key={filter.label}
                                    href={route('memberships.index', filter.value ? { status: filter.value } : {})}
                                    preserveScroll
                                    className={cn(
                                        'inline-flex items-center rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                        filters.status === filter.value || (!filters.status && filter.value === null)
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-sidebar-border/70 bg-background text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {filter.label}
                                </Link>
                            ))}
                        </div>
                    </div>

                    <Card className="border-sidebar-border/70">
                        <CardContent className="space-y-3 p-4">
                            {memberships.data.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center">
                                    <p className="font-medium">No memberships match this filter.</p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        That is either good news or a sign your seed data is boring.
                                    </p>
                                </div>
                            ) : (
                                memberships.data.map((membership) => (
                                    <div key={membership.id} className="rounded-2xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/30">
                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div className="space-y-2">
                                                <div>
                                                    <p className="font-medium">{membership.userName}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {membership.userEmail}
                                                        {membership.userPhone ? ` · ${membership.userPhone}` : ''}
                                                    </p>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    {membership.userRole && <Badge variant="outline">{humanizeStatus(membership.userRole)}</Badge>}
                                                    <Badge variant="outline">{membership.planName}</Badge>
                                                    <Badge variant={membership.autoRenew ? 'default' : 'secondary'}>
                                                        {membership.autoRenew ? 'Auto renew on' : 'Manual renewal'}
                                                    </Badge>
                                                </div>
                                            </div>

                                            <div className="flex flex-col items-start gap-3 xl:items-end">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge variant={badgeVariantForStatus(membership.status)}>{humanizeStatus(membership.status)}</Badge>
                                                    <Badge variant="outline">{formatCurrency(membership.price, membership.currency)}</Badge>
                                                </div>

                                                {canManageMemberships && (
                                                    <div className="flex flex-wrap gap-2">
                                                        <MembershipSettingsDialog membership={membership} />
                                                        <PaymentEventDialog
                                                            membership={membership}
                                                            paymentEventTypes={paymentEventTypes}
                                                            paymentEventStatuses={paymentEventStatuses}
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="mt-4 grid gap-3 md:grid-cols-4">
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Time left</p>
                                                <p className="mt-2 text-sm font-medium">{formatDays(membership.daysRemaining)}</p>
                                            </div>
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Renews on</p>
                                                <p className="mt-2 text-sm font-medium">{membership.renewsAt ?? 'Not scheduled'}</p>
                                            </div>
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Access ends</p>
                                                <p className="mt-2 text-sm font-medium">{membership.effectiveEndsAt ?? 'Open ended'}</p>
                                            </div>
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Grace / cancelled</p>
                                                <p className="mt-2 text-sm font-medium">
                                                    {membership.graceEndsAt ?? membership.cancelledAt ?? 'None'}
                                                </p>
                                            </div>
                                        </div>

                                        {membership.notes && (
                                            <div className="mt-4 rounded-xl border border-sidebar-border/70 bg-background/80 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Notes</p>
                                                <p className="mt-2 text-sm leading-6">{membership.notes}</p>
                                            </div>
                                        )}

                                        <div className="mt-4 space-y-3">
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-medium">Recent payment activity</p>
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Last 4 events</p>
                                            </div>

                                            {membership.paymentEvents.length === 0 ? (
                                                <div className="rounded-xl border border-dashed border-sidebar-border/70 p-4 text-sm text-muted-foreground">
                                                    No payment events recorded yet. That is not documentation. That is denial.
                                                </div>
                                            ) : (
                                                membership.paymentEvents.map((event) => (
                                                    <div
                                                        key={event.id}
                                                        className="grid gap-3 rounded-xl border border-sidebar-border/70 p-3 md:grid-cols-[1fr_1fr_1.2fr]"
                                                    >
                                                        <div className="space-y-1">
                                                            <div className="flex flex-wrap gap-2">
                                                                <Badge variant={badgeVariantForStatus(event.status)}>{humanizeStatus(event.status)}</Badge>
                                                                <Badge variant="outline">{humanizeStatus(event.eventType)}</Badge>
                                                            </div>
                                                            <p className="text-sm text-muted-foreground">{event.provider ?? 'Manual'}{event.reference ? ` · ${event.reference}` : ''}</p>
                                                        </div>

                                                        <div className="space-y-1">
                                                            <p className="text-sm font-medium">
                                                                {event.amount === null ? 'No amount' : formatCurrency(event.amount, event.currency)}
                                                            </p>
                                                            <p className="text-sm text-muted-foreground">{formatDateTime(event.eventAt)}</p>
                                                        </div>

                                                        <div className="space-y-1">
                                                            <p className="text-sm text-muted-foreground">{event.createdBy ? `Recorded by ${event.createdBy}` : 'System event'}</p>
                                                            <p className="text-sm leading-6">{event.notes ?? 'No notes attached.'}</p>
                                                        </div>
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
                        <Button variant="outline" asChild disabled={!memberships.prev_page_url}>
                            {memberships.prev_page_url ? (
                                <Link href={memberships.prev_page_url} preserveScroll>
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

                        <p className="text-sm text-muted-foreground">
                            Page {memberships.current_page} of {memberships.last_page}
                        </p>

                        <Button variant="outline" asChild disabled={!memberships.next_page_url}>
                            {memberships.next_page_url ? (
                                <Link href={memberships.next_page_url} preserveScroll>
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
