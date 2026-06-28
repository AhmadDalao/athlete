import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CreditCard, Dumbbell, Shield, Smartphone, Users } from 'lucide-react';

interface Profile {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    primaryGoal: string | null;
    position: string | null;
    preferredContactMethod: string | null;
    registrationChannel: string | null;
    emailVerifiedAt: string | null;
    phoneVerifiedAt: string | null;
    createdAt: string | null;
    updatedAt: string | null;
    roles: string[];
    primaryRole: string | null;
    permissions: string[];
    permissionCount: number;
    stripeCustomerId: string | null;
}

interface PermissionGroup {
    key: string;
    label: string;
    permissions: Array<{
        key: string;
        description: string;
        enabled: boolean;
    }>;
}

interface UserShowProps {
    profile: Profile;
    permissionGroups: PermissionGroup[];
    summary: {
        currentPlan: string;
        currentStatus: string;
        subscribedAt: string | null;
        daysRemaining: number | null;
        memberships: number;
        paymentEvents: number;
        deviceConnections: number;
        activeCoachAssignments: number;
        activeAthleteAssignments: number;
        trainingProgramsAsAthlete: number;
        trainingProgramsAsCoach: number;
        latestCheckInAt: string | null;
    };
    memberships: Array<{
        id: number;
        planName: string;
        status: string;
        startsAt: string | null;
        renewsAt: string | null;
        endsAt: string | null;
        daysRemaining: number | null;
        autoRenew: boolean;
        price: number;
        currency: string;
        billingProvider: string | null;
        providerSubscriptionId: string | null;
        createdAt: string | null;
    }>;
    payments: Array<{
        id: number;
        eventType: string;
        status: string;
        provider: string | null;
        reference: string | null;
        amount: number | null;
        currency: string;
        eventAt: string | null;
        planName: string;
    }>;
    devices: Array<{
        id: number;
        provider: string;
        status: string;
        authType: string | null;
        lastSyncedAt: string | null;
        lastErrorAt: string | null;
        lastErrorMessage: string | null;
        latestMetricDate: string | null;
        latestReadiness: number | null;
    }>;
    coachAssignments: Array<{
        id: number;
        coachName: string;
        coachEmail: string;
        status: string;
        goal: string | null;
        startedAt: string | null;
        endedAt: string | null;
    }>;
    athleteAssignments: Array<{
        id: number;
        athleteId: number;
        athleteName: string;
        athleteEmail: string;
        status: string;
        goal: string | null;
        startedAt: string | null;
        endedAt: string | null;
    }>;
    programs: Array<{
        id: number;
        title: string;
        role: string;
        counterparty: string;
        counterpartyId: number;
        counterpartyIsAthlete: boolean;
        status: string;
        startDate: string | null;
        endDate: string | null;
        sessionCount: number;
    }>;
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDate(value: string | null) {
    return value ?? 'Not set';
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

function formatCurrency(amount: number | null, currency: string) {
    if (amount === null) {
        return 'No amount';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount);
}

function badgeVariantForStatus(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['past_due', 'failed', 'cancelled', 'expired', 'disconnected', 'attention'].includes(status)) {
        return 'destructive';
    }

    if (['pending', 'paused', 'grace', 'trialing'].includes(status)) {
        return 'secondary';
    }

    if (['active', 'connected', 'succeeded'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

export default function AdminUserShow({
    profile,
    permissionGroups,
    summary,
    memberships,
    payments,
    devices,
    coachAssignments,
    athleteAssignments,
    programs,
}: UserShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin users', href: '/admin/users' },
        { title: profile.name, href: `/admin/users/${profile.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${profile.name} profile`} />

            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow="User profile"
                    title={profile.name}
                    description="Account, subscription, roster, device, and training context in one admin view."
                    badges={[
                        humanizeStatus(profile.primaryRole ?? 'user'),
                        profile.position ?? 'No position set',
                        `${profile.permissionCount} permissions`,
                        `Joined ${formatDate(profile.createdAt)}`,
                    ]}
                    actions={
                        <>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white">
                                <Link href={route('admin.users.index')}>
                                    <ArrowLeft className="size-4" />
                                    Back to users
                                </Link>
                            </Button>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/memberships">Open memberships</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Contact</p>
                                <p className="mt-3 text-sm font-medium text-stone-950">{profile.email}</p>
                                <p className="mt-1 text-sm text-stone-600">{profile.phone ?? 'No phone on record'}</p>
                            </div>
                            <div className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Subscribed</p>
                                <p className="mt-3 text-sm font-medium text-stone-950">{formatDate(summary.subscribedAt)}</p>
                                <p className="mt-1 text-sm text-stone-600">
                                    {summary.currentPlan} · {formatDays(summary.daysRemaining)}
                                </p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Current plan"
                        value={summary.currentPlan}
                        note={humanizeStatus(summary.currentStatus)}
                        icon={CreditCard}
                    />
                    <WorkspaceMetricCard
                        title="Memberships"
                        value={summary.memberships.toString()}
                        note={`${summary.paymentEvents} payment event(s) recorded.`}
                        icon={Shield}
                    />
                    <WorkspaceMetricCard
                        title="Devices"
                        value={summary.deviceConnections.toString()}
                        note={`Latest check-in: ${formatDate(summary.latestCheckInAt)}.`}
                        icon={Smartphone}
                    />
                    <WorkspaceMetricCard
                        title="Roster links"
                        value={(summary.activeCoachAssignments + summary.activeAthleteAssignments).toString()}
                        note={`${summary.trainingProgramsAsAthlete + summary.trainingProgramsAsCoach} training program(s).`}
                        icon={Users}
                    />
                </section>

                <WorkspacePanel title="Account details" description="Identity, roles, signup source, and verification state." contentClassName="p-0">
                    <WorkspaceTable minWidth="min-w-[760px]">
                        <WorkspaceTableHeader labels={['Field', 'Value', 'Field', 'Value']} />
                        <tbody className="divide-y divide-stone-200">
                            <tr>
                                <td className="px-5 py-4 font-medium text-stone-950">Email</td>
                                <td className="px-5 py-4 text-stone-600">{profile.email}</td>
                                <td className="px-5 py-4 font-medium text-stone-950">Phone</td>
                                <td className="px-5 py-4 text-stone-600">{profile.phone ?? 'Not set'}</td>
                            </tr>
                            <tr>
                                <td className="px-5 py-4 font-medium text-stone-950">Roles</td>
                                <td className="px-5 py-4">
                                    <div className="flex flex-wrap gap-2">
                                        {profile.roles.map((role) => (
                                            <Badge key={role} variant="outline">
                                                {humanizeStatus(role)}
                                            </Badge>
                                        ))}
                                    </div>
                                </td>
                                <td className="px-5 py-4 font-medium text-stone-950">Signup channel</td>
                                <td className="px-5 py-4 text-stone-600">{humanizeStatus(profile.registrationChannel ?? 'email')}</td>
                            </tr>
                            <tr>
                                <td className="px-5 py-4 font-medium text-stone-950">Position</td>
                                <td className="px-5 py-4 text-stone-600">{profile.position ?? 'Not set'}</td>
                                <td className="px-5 py-4 font-medium text-stone-950">Permissions</td>
                                <td className="px-5 py-4 text-stone-600">{profile.permissionCount} active permission(s)</td>
                            </tr>
                            <tr>
                                <td className="px-5 py-4 font-medium text-stone-950">Email verified</td>
                                <td className="px-5 py-4 text-stone-600">{formatDate(profile.emailVerifiedAt)}</td>
                                <td className="px-5 py-4 font-medium text-stone-950">Phone verified</td>
                                <td className="px-5 py-4 text-stone-600">{formatDate(profile.phoneVerifiedAt)}</td>
                            </tr>
                            <tr>
                                <td className="px-5 py-4 font-medium text-stone-950">Goal</td>
                                <td className="px-5 py-4 text-stone-600">{profile.primaryGoal ?? 'Not set'}</td>
                                <td className="px-5 py-4 font-medium text-stone-950">Stripe customer</td>
                                <td className="px-5 py-4 text-stone-600">{profile.stripeCustomerId ?? 'Not connected'}</td>
                            </tr>
                        </tbody>
                    </WorkspaceTable>
                </WorkspacePanel>

                <WorkspacePanel title="Access permissions" description="Exact controls this account can open or manage." contentClassName="p-0">
                    <WorkspaceTable minWidth="min-w-[860px]">
                        <WorkspaceTableHeader labels={['Group', 'Permission', 'Status', 'Meaning']} />
                        <tbody className="divide-y divide-stone-200">
                            {permissionGroups.flatMap((group) =>
                                group.permissions.map((permission) => (
                                    <tr key={permission.key}>
                                        <td className="px-5 py-4 font-medium text-stone-950">{group.label}</td>
                                        <td className="px-5 py-4 font-mono text-xs text-stone-700">{permission.key}</td>
                                        <td className="px-5 py-4">
                                            <Badge variant={permission.enabled ? 'default' : 'outline'}>
                                                {permission.enabled ? 'Enabled' : 'Off'}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4 text-stone-600">{permission.description}</td>
                                    </tr>
                                )),
                            )}
                        </tbody>
                    </WorkspaceTable>
                </WorkspacePanel>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Commercial"
                        title="Subscription and payment history."
                        description="Track when the client subscribed, how billing is moving, and what events have landed."
                    />
                    <WorkspacePanel title="Memberships" contentClassName="p-0">
                        <WorkspaceTable minWidth="min-w-[860px]">
                            <WorkspaceTableHeader labels={['Plan', 'Status', 'Subscribed', 'Renewal', 'Value', 'Provider']} />
                            {memberships.length === 0 ? (
                                <WorkspaceTableEmpty message="No memberships recorded for this user." colSpan={6} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {memberships.map((membership) => (
                                        <tr key={membership.id} className="align-top">
                                            <td className="px-5 py-4 font-medium text-stone-950">{membership.planName}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={badgeVariantForStatus(membership.status)}>{humanizeStatus(membership.status)}</Badge>
                                                <p className="mt-2 text-xs text-stone-500">
                                                    {membership.autoRenew ? 'Auto-renew' : 'Manual renewal'}
                                                </p>
                                            </td>
                                            <td className="px-5 py-4 text-stone-600">{formatDate(membership.startsAt)}</td>
                                            <td className="px-5 py-4 text-stone-600">
                                                <p>{formatDate(membership.renewsAt ?? membership.endsAt)}</p>
                                                <p className="mt-1 text-xs text-stone-500">{formatDays(membership.daysRemaining)}</p>
                                            </td>
                                            <td className="px-5 py-4 text-stone-600">{formatCurrency(membership.price, membership.currency)}</td>
                                            <td className="px-5 py-4 text-stone-600">
                                                <p>{membership.billingProvider ?? 'Manual'}</p>
                                                <p className="mt-1 text-xs text-stone-500">{membership.providerSubscriptionId ?? 'No provider ID'}</p>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <WorkspacePanel title="Payment events" contentClassName="p-0">
                        <WorkspaceTable minWidth="min-w-[760px]">
                            <WorkspaceTableHeader labels={['Date', 'Type', 'Status', 'Amount', 'Reference']} />
                            {payments.length === 0 ? (
                                <WorkspaceTableEmpty message="No payment events recorded for this user." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {payments.map((event) => (
                                        <tr key={event.id}>
                                            <td className="px-5 py-4 text-stone-600">{formatDate(event.eventAt)}</td>
                                            <td className="px-5 py-4 text-stone-600">{humanizeStatus(event.eventType)}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={badgeVariantForStatus(event.status)}>{humanizeStatus(event.status)}</Badge>
                                            </td>
                                            <td className="px-5 py-4 text-stone-600">{formatCurrency(event.amount, event.currency)}</td>
                                            <td className="px-5 py-4 text-stone-600">{event.reference ?? event.provider ?? 'Manual event'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <WorkspacePanel title="Devices" description="Connection state and latest visible sync signal." contentClassName="p-0">
                        <WorkspaceTable minWidth="min-w-[620px]">
                            <WorkspaceTableHeader labels={['Provider', 'Status', 'Last sync', 'Latest metrics']} />
                            {devices.length === 0 ? (
                                <WorkspaceTableEmpty message="No device connections recorded." colSpan={4} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {devices.map((device) => (
                                        <tr key={device.id}>
                                            <td className="px-5 py-4 text-stone-600">{device.provider}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={badgeVariantForStatus(device.status)}>{humanizeStatus(device.status)}</Badge>
                                            </td>
                                            <td className="px-5 py-4 text-stone-600">{formatDate(device.lastSyncedAt)}</td>
                                            <td className="px-5 py-4 text-stone-600">
                                                {formatDate(device.latestMetricDate)} · readiness {device.latestReadiness ?? 'N/A'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <WorkspacePanel
                        title="Roster assignments"
                        description="Coach and athlete links attached to this user."
                        contentClassName="space-y-4"
                    >
                        <div>
                            <p className="mb-2 text-sm font-medium text-stone-950">As athlete</p>
                            <WorkspaceTable minWidth="min-w-[560px]">
                                <WorkspaceTableHeader labels={['Coach', 'Status', 'Started', 'Goal']} />
                                {coachAssignments.length === 0 ? (
                                    <WorkspaceTableEmpty message="No coach assignment history." colSpan={4} />
                                ) : (
                                    <tbody className="divide-y divide-stone-200">
                                        {coachAssignments.map((assignment) => (
                                            <tr key={assignment.id}>
                                                <td className="px-5 py-4 text-stone-600">{assignment.coachName}</td>
                                                <td className="px-5 py-4">
                                                    <Badge variant={badgeVariantForStatus(assignment.status)}>
                                                        {humanizeStatus(assignment.status)}
                                                    </Badge>
                                                </td>
                                                <td className="px-5 py-4 text-stone-600">{formatDate(assignment.startedAt)}</td>
                                                <td className="px-5 py-4 text-stone-600">{assignment.goal ?? 'No goal'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </div>

                        <div>
                            <p className="mb-2 text-sm font-medium text-stone-950">As coach</p>
                            <WorkspaceTable minWidth="min-w-[560px]">
                                <WorkspaceTableHeader labels={['Athlete', 'Status', 'Started', 'Goal']} />
                                {athleteAssignments.length === 0 ? (
                                    <WorkspaceTableEmpty message="No athlete roster history." colSpan={4} />
                                ) : (
                                    <tbody className="divide-y divide-stone-200">
                                        {athleteAssignments.map((assignment) => (
                                            <tr key={assignment.id}>
                                                <td className="px-5 py-4">
                                                    <Link
                                                        href={route('athletes.show', assignment.athleteId)}
                                                        className="font-medium text-stone-950 underline-offset-4 hover:text-emerald-700 hover:underline"
                                                    >
                                                        {assignment.athleteName}
                                                    </Link>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <Badge variant={badgeVariantForStatus(assignment.status)}>
                                                        {humanizeStatus(assignment.status)}
                                                    </Badge>
                                                </td>
                                                <td className="px-5 py-4 text-stone-600">{formatDate(assignment.startedAt)}</td>
                                                <td className="px-5 py-4 text-stone-600">{assignment.goal ?? 'No goal'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </div>
                    </WorkspacePanel>
                </section>

                <WorkspacePanel title="Training programs" description="Programs connected to the user as an athlete or coach." contentClassName="p-0">
                    <WorkspaceTable minWidth="min-w-[760px]">
                        <WorkspaceTableHeader labels={['Program', 'Role', 'With', 'Status', 'Dates', 'Sessions']} />
                        {programs.length === 0 ? (
                            <WorkspaceTableEmpty message="No training programs recorded for this user." colSpan={6} />
                        ) : (
                            <tbody className="divide-y divide-stone-200">
                                {programs.map((program) => (
                                    <tr key={`${program.role}-${program.id}`}>
                                        <td className="px-5 py-4 font-medium text-stone-950">{program.title}</td>
                                        <td className="px-5 py-4 text-stone-600">{humanizeStatus(program.role)}</td>
                                        <td className="px-5 py-4">
                                            <Link
                                                href={
                                                    program.counterpartyIsAthlete
                                                        ? route('athletes.show', program.counterpartyId)
                                                        : route('admin.users.show', program.counterpartyId)
                                                }
                                                className="font-medium text-stone-950 underline-offset-4 hover:text-emerald-700 hover:underline"
                                            >
                                                {program.counterparty}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge variant={badgeVariantForStatus(program.status)}>{humanizeStatus(program.status)}</Badge>
                                        </td>
                                        <td className="px-5 py-4 text-stone-600">
                                            {formatDate(program.startDate)} to {formatDate(program.endDate)}
                                        </td>
                                        <td className="px-5 py-4 text-stone-600">
                                            <Dumbbell className="mr-2 inline size-4" />
                                            {program.sessionCount}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        )}
                    </WorkspaceTable>
                </WorkspacePanel>
            </div>
        </AppLayout>
    );
}
