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
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
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
    billingProvider: string | null;
    providerSubscriptionId: string | null;
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
    billing: {
        provider: string;
        checkoutEnabled: boolean;
        portalEnabled: boolean;
        webhookEnabled: boolean;
        hasCustomerProfile: boolean;
        currentPlanId: number | null;
        activePlans: {
            id: number;
            name: string;
            description: string | null;
            price: number;
            currency: string;
            durationDays: number;
            billingInterval: string;
            checkoutReady: boolean;
        }[];
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
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
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

function BillingWorkspaceCard({ billing, viewerRole }: Pick<MembershipPageProps, 'billing' | 'viewerRole'>) {
    const page = usePage<SharedData>();
    const [portalProcessing, setPortalProcessing] = useState(false);
    const { data, setData, post, processing, errors } = useForm<{ plan_id: string }>({
        plan_id: billing.currentPlanId ? billing.currentPlanId.toString() : (billing.activePlans[0]?.id.toString() ?? ''),
    });
    const selectedPlan = billing.activePlans.find((plan) => plan.id.toString() === data.plan_id) ?? null;
    const flashStatus = page.props.flash?.status;

    const openCheckout = () => {
        post(route('billing.checkout.store'), {
            preserveScroll: true,
        });
    };

    const openPortal = () => {
        setPortalProcessing(true);

        post(route('billing.portal.store'), {
            preserveScroll: true,
            onFinish: () => setPortalProcessing(false),
        });
    };

    return (
        <WorkspacePanel
            title="Billing access"
            description={
                viewerRole === 'athlete'
                    ? 'Pick the right plan, launch Stripe checkout, and let the portal handle card updates without dragging a coach into admin work.'
                    : 'Use Stripe for self-serve billing while keeping the internal membership queue visible for operator cleanup.'
            }
            contentClassName="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]"
        >
            <div className="space-y-4 rounded-2xl border border-stone-200/75 bg-stone-50/70 p-5">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">{billing.provider.toUpperCase()}</Badge>
                    <Badge variant={billing.checkoutEnabled ? 'default' : 'secondary'}>
                        {billing.checkoutEnabled ? 'Checkout live' : 'Checkout staged'}
                    </Badge>
                    <Badge variant={billing.webhookEnabled ? 'default' : 'secondary'}>
                        {billing.webhookEnabled ? 'Webhook signed' : 'Webhook missing'}
                    </Badge>
                </div>

                {flashStatus && (
                    <div className="rounded-xl border border-stone-200/75 bg-white/90 p-3 text-sm leading-6 text-stone-700">{flashStatus}</div>
                )}

                <div className="grid gap-2">
                    <Label htmlFor="billing-plan">Plan</Label>
                    <Select value={data.plan_id} onValueChange={(value) => setData('plan_id', value)}>
                        <SelectTrigger id="billing-plan">
                            <SelectValue placeholder="Select a plan" />
                        </SelectTrigger>
                        <SelectContent>
                            {billing.activePlans.map((plan) => (
                                <SelectItem key={plan.id} value={plan.id.toString()}>
                                    {plan.name} · {formatCurrency(plan.price, plan.currency)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.plan_id} />
                </div>

                {selectedPlan && (
                    <div className="rounded-xl border border-stone-200/75 bg-white/90 p-4">
                        <p className="font-medium text-stone-950">{selectedPlan.name}</p>
                        <p className="mt-1 text-sm text-stone-600">
                            {selectedPlan.description ?? 'No plan description yet. Fix that before real customers see it.'}
                        </p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <Badge variant="outline">{humanizeStatus(selectedPlan.billingInterval)}</Badge>
                            <Badge variant="outline">{selectedPlan.durationDays} day cycle</Badge>
                            <Badge variant={selectedPlan.checkoutReady ? 'default' : 'secondary'}>
                                {selectedPlan.checkoutReady ? 'Stripe price linked' : 'Needs Stripe price ID'}
                            </Badge>
                        </div>
                    </div>
                )}

                <div className="flex flex-wrap gap-3">
                    <Button
                        type="button"
                        onClick={openCheckout}
                        disabled={processing || !billing.checkoutEnabled || !selectedPlan || !selectedPlan.checkoutReady}
                    >
                        <CreditCard className="mr-2 size-4" />
                        Open checkout
                    </Button>
                    <Button type="button" variant="outline" onClick={openPortal} disabled={portalProcessing || !billing.portalEnabled}>
                        <Settings2 className="mr-2 size-4" />
                        Open billing portal
                    </Button>
                </div>
            </div>

            <div className="space-y-3 rounded-2xl border border-stone-200/75 bg-white/92 p-5">
                <div className="rounded-xl border border-stone-200/75 bg-stone-50/70 p-3">
                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Checkout readiness</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">
                        Checkout works only when the Stripe secret key is set and the selected plan has a real Stripe price ID.
                    </p>
                </div>
                <div className="rounded-xl border border-stone-200/75 bg-stone-50/70 p-3">
                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Portal access</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">
                        {billing.hasCustomerProfile
                            ? 'This account already has a Stripe customer profile, so the portal can manage card details and invoices.'
                            : 'Portal access appears after the first successful Stripe checkout creates a customer profile.'}
                    </p>
                </div>
                <div className="rounded-xl border border-stone-200/75 bg-stone-50/70 p-3">
                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Launch note</p>
                    <p className="mt-2 text-sm leading-6 text-stone-600">
                        Webhook signing must be configured before you trust renewal automation. Skipping that would be dumb.
                    </p>
                </div>
            </div>
        </WorkspacePanel>
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
    billing,
    memberships,
    auditCommand,
    paymentEventTypes,
    paymentEventStatuses,
}: MembershipPageProps) {
    const primaryMembership = memberships.data[0] ?? null;
    const recentPaymentActivity = memberships.data
        .flatMap((membership) =>
            membership.paymentEvents.map((event) => ({
                ...event,
                userName: membership.userName,
                planName: membership.planName,
            })),
        )
        .sort((left, right) => {
            const leftTime = left.eventAt ? new Date(left.eventAt).getTime() : 0;
            const rightTime = right.eventAt ? new Date(right.eventAt).getTime() : 0;

            return rightTime - leftTime;
        })
        .slice(0, 8);

    const heroTitle =
        viewerRole === 'athlete' ? 'Your membership should be obvious, not mysterious.' : 'Billing, renewals, and reality all in one place.';
    const heroDescription =
        viewerRole === 'athlete'
            ? 'Plan status, days remaining, checkout, and recent payments sit here without operator noise taking over the page.'
            : `${scopeLabel}. This is the billing reality check: status, renewals, money movement, and who is about to become a support ticket.`;
    const heroBadges = [
        viewerRole === 'athlete' ? 'Athlete view' : 'Operator view',
        billing.provider.toUpperCase(),
        billing.webhookEnabled ? 'Webhook ready' : 'Webhook missing',
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Memberships" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <WorkspaceHero
                    eyebrow="Membership workspace"
                    title={heroTitle}
                    description={heroDescription}
                    badges={heroBadges}
                    actions={
                        <>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/dashboard">Back to dashboard</Link>
                            </Button>
                            {viewerRole === 'admin' ? (
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                    <Link href="/admin/control-center">Open control center</Link>
                                </Button>
                            ) : canManageMemberships ? (
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                    <Link href="/roster">Open roster</Link>
                                </Button>
                            ) : (
                                <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                    <Link href="/training">Open training</Link>
                                </Button>
                            )}
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">
                                    {viewerRole === 'athlete' ? 'Current plan' : 'Projected monthly revenue'}
                                </p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {viewerRole === 'athlete'
                                        ? (primaryMembership?.planName ?? 'No plan')
                                        : formatCurrency(summary.projectedMonthlyRevenue)}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {viewerRole === 'athlete'
                                        ? primaryMembership
                                            ? formatDays(primaryMembership.daysRemaining)
                                            : 'No active membership found.'
                                        : 'Normalized from active memberships and plan intervals.'}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">
                                    {viewerRole === 'athlete' ? 'Renewal state' : 'Collected this month'}
                                </p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {viewerRole === 'athlete'
                                        ? humanizeStatus(primaryMembership?.status ?? 'unknown')
                                        : formatCurrency(summary.paymentVolumeThisMonth)}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {viewerRole === 'athlete'
                                        ? primaryMembership?.autoRenew
                                            ? 'Auto renew is enabled.'
                                            : 'Manual renewal is in effect.'
                                        : `${summary.failedPaymentsThisMonth} payment event(s) failed this month.`}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">
                                    {viewerRole === 'athlete' ? 'Recent payments' : 'Auto renew enabled'}
                                </p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">
                                    {viewerRole === 'athlete' ? recentPaymentActivity.length : summary.autoRenewEnabled}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {viewerRole === 'athlete'
                                        ? 'Recent payment events visible in this workspace.'
                                        : 'Memberships currently set to renew automatically.'}
                                </p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {viewerRole === 'athlete' ? (
                        <>
                            <WorkspaceMetricCard
                                title="Access state"
                                value={humanizeStatus(primaryMembership?.status ?? 'unknown')}
                                note="This is the status the platform will actually use."
                                icon={ShieldCheck}
                            />
                            <WorkspaceMetricCard
                                title="Days remaining"
                                value={primaryMembership?.daysRemaining === null ? 'N/A' : String(primaryMembership?.daysRemaining ?? 'N/A')}
                                note={primaryMembership ? formatDays(primaryMembership.daysRemaining) : 'No membership record found.'}
                                icon={Timer}
                            />
                            <WorkspaceMetricCard
                                title="Plan value"
                                value={primaryMembership ? formatCurrency(primaryMembership.price, primaryMembership.currency) : 'N/A'}
                                note="Current billing value for the visible plan."
                                icon={CreditCard}
                            />
                            <WorkspaceMetricCard
                                title="Payment events"
                                value={recentPaymentActivity.length.toString()}
                                note="Recent payment history visible from this page."
                                icon={AlertTriangle}
                            />
                        </>
                    ) : (
                        <>
                            <WorkspaceMetricCard
                                title="Tracked memberships"
                                value={summary.totalMemberships.toString()}
                                note={`${memberships.total} record(s) match the current view.`}
                                icon={CreditCard}
                            />
                            <WorkspaceMetricCard
                                title="Current access"
                                value={summary.currentAccess.toString()}
                                note="Includes active, trialing, grace, past-due, and scheduled-to-end access."
                                icon={ShieldCheck}
                            />
                            <WorkspaceMetricCard
                                title="Attention required"
                                value={summary.attentionRequired.toString()}
                                note="Grace, past due, and cancelled memberships should not sit ignored."
                                icon={AlertTriangle}
                            />
                            <WorkspaceMetricCard
                                title="Renewing this week"
                                value={summary.renewingThisWeek.toString()}
                                note="Seven-day renewal horizon for manual follow-up."
                                icon={Timer}
                            />
                        </>
                    )}
                </section>

                <BillingWorkspaceCard billing={billing} viewerRole={viewerRole} />

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Membership queue"
                        title={
                            viewerRole === 'athlete' ? 'Current access and renewal detail.' : 'Filter the queue and keep the next billing mess small.'
                        }
                        description={
                            viewerRole === 'athlete'
                                ? 'The operator detail is still here, but your current plan and recent activity are the things that matter first.'
                                : 'Filters, dialogs, and pagination behave the same; the page just stops looking like three design eras stapled together.'
                        }
                    />

                    <WorkspacePanel
                        title={viewerRole === 'athlete' ? 'Membership records' : 'Membership queue'}
                        description={viewerRole === 'athlete' ? 'Your visible membership history.' : 'Review status, dates, and operator actions.'}
                        contentClassName="space-y-4"
                    >
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <p className="text-sm leading-6 text-stone-600">
                                {viewerRole === 'athlete'
                                    ? `${memberships.total} visible membership record(s) in this view.`
                                    : `${memberships.total} record(s) match the current filter.`}
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {statusFilters.map((filter) => (
                                    <Link
                                        key={filter.label}
                                        href={route('memberships.index', filter.value ? { status: filter.value } : {})}
                                        preserveScroll
                                        preserveState
                                        replace
                                        only={['filters', 'summary', 'memberships']}
                                        className={cn(
                                            'inline-flex items-center rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                            filters.status === filter.value || (!filters.status && filter.value === null)
                                                ? 'border-stone-900 bg-stone-900 text-white'
                                                : 'border-stone-200/80 bg-white text-stone-600 hover:border-stone-300 hover:text-stone-950',
                                        )}
                                    >
                                        {filter.label}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        <WorkspaceTable minWidth="min-w-[1180px]">
                            <WorkspaceTableHeader
                                labels={['Member', 'Role', 'Plan', 'Status', 'Renewal', 'Access ends', 'Value', 'Provider', 'Actions']}
                            />
                            {memberships.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No memberships match this filter." colSpan={9} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {memberships.data.map((membership) => (
                                        <tr key={membership.id} className="align-top transition-colors hover:bg-stone-50/80">
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-stone-950">{membership.userName}</p>
                                                <p className="mt-1 text-xs text-stone-500">{membership.userEmail}</p>
                                                {membership.userPhone && <p className="mt-1 text-xs text-stone-500">{membership.userPhone}</p>}
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">{humanizeStatus(membership.userRole ?? 'user')}</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{membership.planName}</p>
                                                <p className="mt-1 text-xs text-stone-500">
                                                    {membership.autoRenew ? 'Auto renew on' : 'Manual renewal'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(membership.status)}>
                                                    {humanizeStatus(membership.status)}
                                                </Badge>
                                                <p className="mt-2 text-xs font-medium text-emerald-700">{formatDays(membership.daysRemaining)}</p>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-stone-700">{membership.renewsAt ?? 'Not scheduled'}</td>
                                            <td className="px-4 py-4">
                                                <p className="text-sm text-stone-700">{membership.effectiveEndsAt ?? 'Open ended'}</p>
                                                <p className="mt-1 text-xs text-stone-500">
                                                    Grace/cancelled: {membership.graceEndsAt ?? membership.cancelledAt ?? 'None'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4 font-medium text-stone-950">
                                                {formatCurrency(membership.price, membership.currency)}
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="text-sm text-stone-700">{membership.billingProvider?.toUpperCase() ?? 'Manual'}</p>
                                                <p className="mt-1 line-clamp-2 max-w-[12rem] text-xs text-stone-500">
                                                    {membership.providerSubscriptionId ?? membership.notes ?? 'No provider id'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {canManageMemberships ? (
                                                    <div className="flex flex-col gap-2">
                                                        <MembershipSettingsDialog membership={membership} />
                                                        <PaymentEventDialog
                                                            membership={membership}
                                                            paymentEventTypes={paymentEventTypes}
                                                            paymentEventStatuses={paymentEventStatuses}
                                                        />
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-stone-500">
                                                        {membership.paymentEvents.length} recent payment(s)
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Payments"
                        title="Recent payment activity stays visible, but lower than access status."
                        description="Technical billing evidence is still here. It just stops shouting louder than the actual membership state."
                    />

                    <WorkspacePanel
                        title="Recent payment activity"
                        description="Latest visible payment events across the current membership scope."
                        contentClassName="space-y-3"
                    >
                        <WorkspaceTable minWidth="min-w-[980px]">
                            <WorkspaceTableHeader labels={['When', 'Member', 'Plan', 'Type', 'Status', 'Amount', 'Provider / reference', 'Notes']} />
                            {recentPaymentActivity.length === 0 ? (
                                <WorkspaceTableEmpty message="No payment events recorded yet." colSpan={8} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {recentPaymentActivity.map((event) => (
                                        <tr key={`${event.userName}-${event.id}`} className="align-top transition-colors hover:bg-stone-50/80">
                                            <td className="px-4 py-4 text-sm text-stone-700">{formatDateTime(event.eventAt)}</td>
                                            <td className="px-4 py-4 font-medium text-stone-950">{event.userName}</td>
                                            <td className="px-4 py-4 text-sm text-stone-700">{event.planName}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">{humanizeStatus(event.eventType)}</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(event.status)}>{humanizeStatus(event.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 font-medium text-stone-950">
                                                {event.amount === null ? 'No amount' : formatCurrency(event.amount, event.currency)}
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="text-sm text-stone-700">{event.provider ?? 'Manual'}</p>
                                                <p className="mt-1 max-w-[14rem] break-words text-xs text-stone-500">{event.reference ?? 'No reference'}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="max-w-[18rem] text-sm leading-6 text-stone-700">{event.notes ?? 'No notes attached.'}</p>
                                                <p className="mt-1 text-xs text-stone-500">
                                                    {event.createdBy ? `Recorded by ${event.createdBy}` : 'System event'}
                                                </p>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>

                {canManageMemberships && (
                    <section className="space-y-4">
                        <WorkspaceSectionHeading
                            eyebrow="Technical detail"
                            title="Keep the operator and provider detail available, just not in the hero."
                            description="Audit commands, provider state, and other backend-facing detail still matter. They simply belong lower in the visual hierarchy."
                        />

                        <WorkspacePanel
                            title="Operator detail"
                            description="Billing provider context and commands used to inspect the queue."
                            contentClassName="grid gap-3 lg:grid-cols-[0.8fr_1.2fr]"
                        >
                            <div className="space-y-3 rounded-2xl border border-stone-200/75 bg-stone-50/70 p-4">
                                <div className="rounded-xl border border-stone-200/75 bg-white/90 p-3">
                                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Provider</p>
                                    <p className="mt-2 text-sm font-medium text-stone-950">{billing.provider.toUpperCase()}</p>
                                </div>
                                <div className="rounded-xl border border-stone-200/75 bg-white/90 p-3">
                                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Portal state</p>
                                    <p className="mt-2 text-sm font-medium text-stone-950">
                                        {billing.portalEnabled ? 'Portal ready' : 'Portal disabled'}
                                    </p>
                                </div>
                                <div className="rounded-xl border border-stone-200/75 bg-white/90 p-3">
                                    <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Webhook state</p>
                                    <p className="mt-2 text-sm font-medium text-stone-950">
                                        {billing.webhookEnabled ? 'Signed and active' : 'Missing or disabled'}
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-2xl border border-dashed border-stone-200/80 bg-white/90 p-4">
                                <p className="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Audit command</p>
                                <code className="mt-3 block text-sm leading-6 text-stone-700">{auditCommand}</code>
                            </div>
                        </WorkspacePanel>
                    </section>
                )}

                <section>
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

                        <p className="text-muted-foreground text-sm">
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
