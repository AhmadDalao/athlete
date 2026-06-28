import { Badge } from '@/components/ui/badge';
import { WorkspaceActionCard, WorkspaceMetricCard, WorkspacePanel, WorkspaceSectionHeading } from '@/components/workspace-primitives';
import { badgeVariantForStatus, formatCurrency, formatDays, humanizeStatus } from '@/pages/dashboard-view/helpers';
import { type AdminOverview } from '@/pages/dashboard-view/types';
import { Link } from '@inertiajs/react';
import { Activity, ArrowRight, CreditCard, FileClock, MailCheck, Shield, Users, Watch, WifiOff } from 'lucide-react';
import { type ReactNode } from 'react';

function formatDate(value: string | null) {
    return value ?? 'Not set';
}

function TableShell({ children }: { children: ReactNode }) {
    return <div className="overflow-hidden rounded-lg border border-stone-200 bg-white">{children}</div>;
}

function TableHeader({ labels }: { labels: string[] }) {
    return (
        <thead className="bg-stone-50 text-left text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
            <tr>
                {labels.map((label) => (
                    <th key={label} className="px-4 py-3">
                        {label}
                    </th>
                ))}
            </tr>
        </thead>
    );
}

function EmptyRows({ message, colSpan }: { message: string; colSpan: number }) {
    return (
        <tbody>
            <tr>
                <td colSpan={colSpan} className="px-4 py-8 text-center text-sm text-stone-500">
                    {message}
                </td>
            </tr>
        </tbody>
    );
}

export function AdminDashboardComposition({ admin }: { admin: AdminOverview }) {
    return (
        <section id="admin-board" className="space-y-6">
            <WorkspaceSectionHeading
                eyebrow="Insights and metrics"
                title="Admin dashboard"
                description="Key users, athletes, memberships, payments, and device issues in one scan."
            />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <WorkspaceMetricCard
                    title="Renewals in range"
                    value={admin.metrics.expiringMemberships.toString()}
                    note={`${admin.metrics.activeMemberships} memberships are currently live.`}
                    icon={CreditCard}
                />
                <WorkspaceMetricCard
                    title="Payment issues"
                    value={admin.metrics.failedPaymentsThisMonth.toString()}
                    note={`${formatCurrency(admin.metrics.paymentVolumeThisMonth)} collected this month.`}
                    icon={Shield}
                />
                <WorkspaceMetricCard
                    title="Device issues"
                    value={admin.metrics.attentionConnections.toString()}
                    note={`${admin.metrics.connectedDevices} connected devices across the platform.`}
                    icon={WifiOff}
                />
                <WorkspaceMetricCard
                    title="Projected revenue"
                    value={formatCurrency(admin.metrics.projectedMonthlyRevenue)}
                    note={`Coach payout liability is tracking at ${formatCurrency(admin.metrics.coachPayoutLiability)}.`}
                    icon={CreditCard}
                />
                <WorkspaceMetricCard
                    title="User mix"
                    value={`${admin.metrics.totalUsers}`}
                    note={`${admin.metrics.totalCoaches} coaches and ${admin.metrics.totalAthletes} athletes are live.`}
                    icon={Users}
                />
                <WorkspaceMetricCard
                    title="Training activity"
                    value={admin.metrics.loggedSessionsThisWeek.toString()}
                    note={`${admin.metrics.scheduledSessionsThisWeek} sessions are scheduled over the next week.`}
                    icon={Activity}
                />
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <WorkspacePanel
                    title="Users"
                    description="Newest accounts with role, signup channel, membership state, and profile access."
                    contentClassName="p-0"
                >
                    <TableShell>
                        <table className="w-full min-w-[720px] text-sm">
                            <TableHeader labels={['User', 'Role', 'Joined', 'Membership', 'Profile']} />
                            {admin.recentUsers.length === 0 ? (
                                <EmptyRows message="No users are available yet." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {admin.recentUsers.map((user) => (
                                        <tr key={user.id} className="align-top">
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{user.name}</p>
                                                <p className="mt-1 text-xs text-stone-500">{user.email}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">{humanizeStatus(user.primaryRole ?? 'user')}</Badge>
                                                <p className="mt-2 text-xs text-stone-500">{humanizeStatus(user.registrationChannel ?? 'email')}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{formatDate(user.createdAt)}</td>
                                            <td className="px-4 py-4">
                                                {user.currentMembership ? (
                                                    <div className="space-y-2">
                                                        <Badge variant={badgeVariantForStatus(user.currentMembership.status)}>
                                                            {humanizeStatus(user.currentMembership.status)}
                                                        </Badge>
                                                        <p className="text-xs text-stone-500">
                                                            {user.currentMembership.planName} · {formatDays(user.currentMembership.daysRemaining)}
                                                        </p>
                                                        <p className="text-xs text-stone-500">
                                                            Subscribed {formatDate(user.currentMembership.subscribedAt)}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-stone-500">No membership</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <Link
                                                    href={route('admin.users.show', user.id)}
                                                    className="inline-flex items-center gap-2 font-medium text-stone-950 hover:text-stone-600"
                                                >
                                                    Open
                                                    <ArrowRight className="size-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </table>
                    </TableShell>
                </WorkspacePanel>

                <WorkspacePanel
                    title="Athletes"
                    description="Athlete tracking view with coach coverage, program count, check-ins, and subscribed date."
                    contentClassName="p-0"
                >
                    <TableShell>
                        <table className="w-full min-w-[680px] text-sm">
                            <TableHeader labels={['Athlete', 'Coach', 'Activity', 'Subscription', 'Profile']} />
                            {admin.athleteTable.length === 0 ? (
                                <EmptyRows message="No athletes are available yet." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {admin.athleteTable.map((athlete) => (
                                        <tr key={athlete.id} className="align-top">
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{athlete.name}</p>
                                                <p className="mt-1 text-xs text-stone-500">{athlete.email}</p>
                                                <p className="mt-2 max-w-[240px] text-xs leading-5 text-stone-500">{athlete.goal ?? 'No goal set'}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {athlete.coachNames.length > 0 ? athlete.coachNames.join(', ') : 'No active coach'}
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                <p>{athlete.trainingProgramsCount} programs</p>
                                                <p>{athlete.deviceConnectionsCount} devices</p>
                                                <p>Check-in {formatDate(athlete.latestCheckInAt)}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {athlete.currentMembership ? (
                                                    <div className="space-y-2">
                                                        <Badge variant={badgeVariantForStatus(athlete.currentMembership.status)}>
                                                            {humanizeStatus(athlete.currentMembership.status)}
                                                        </Badge>
                                                        <p className="text-xs text-stone-500">{athlete.currentMembership.planName}</p>
                                                        <p className="text-xs text-stone-500">
                                                            Subscribed {formatDate(athlete.currentMembership.subscribedAt)}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-stone-500">No subscription</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <Link
                                                    href={route('admin.users.show', athlete.id)}
                                                    className="inline-flex items-center gap-2 font-medium text-stone-950 hover:text-stone-600"
                                                >
                                                    Open
                                                    <ArrowRight className="size-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </table>
                    </TableShell>
                </WorkspacePanel>
            </div>

            <WorkspacePanel
                title="Subscriptions"
                description="Latest subscriptions with start date, renewal window, value, and owner profile."
                contentClassName="p-0"
            >
                <TableShell>
                    <table className="w-full min-w-[920px] text-sm">
                        <TableHeader labels={['Client', 'Plan', 'Status', 'Subscribed', 'Renewal', 'Value', 'Profile']} />
                        {admin.subscriptionTable.length === 0 ? (
                            <EmptyRows message="No subscriptions are available yet." colSpan={7} />
                        ) : (
                            <tbody className="divide-y divide-stone-200">
                                {admin.subscriptionTable.map((membership) => (
                                    <tr key={membership.id} className="align-top">
                                        <td className="px-4 py-4">
                                            <p className="font-medium text-stone-950">{membership.userName}</p>
                                            <p className="mt-1 text-xs text-stone-500">{membership.userEmail}</p>
                                        </td>
                                        <td className="px-4 py-4 text-stone-600">{membership.planName}</td>
                                        <td className="px-4 py-4">
                                            <Badge variant={badgeVariantForStatus(membership.status)}>{humanizeStatus(membership.status)}</Badge>
                                            <p className="mt-2 text-xs text-stone-500">{membership.autoRenew ? 'Auto-renew on' : 'Manual renewal'}</p>
                                        </td>
                                        <td className="px-4 py-4 text-stone-600">{formatDate(membership.startsAt)}</td>
                                        <td className="px-4 py-4 text-stone-600">
                                            <p>{formatDate(membership.renewsAt ?? membership.endsAt)}</p>
                                            <p className="mt-1 text-xs text-stone-500">{formatDays(membership.daysRemaining)}</p>
                                        </td>
                                        <td className="px-4 py-4 text-stone-600">
                                            {formatCurrency(membership.price)} {membership.currency}
                                            <p className="mt-1 text-xs text-stone-500">{membership.billingProvider ?? 'Manual'}</p>
                                        </td>
                                        <td className="px-4 py-4">
                                            <Link
                                                href={route('admin.users.show', membership.userId)}
                                                className="inline-flex items-center gap-2 font-medium text-stone-950 hover:text-stone-600"
                                            >
                                                Open
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        )}
                    </table>
                </TableShell>
            </WorkspacePanel>

            <div className="space-y-4">
                <WorkspaceSectionHeading
                    eyebrow="Urgent"
                    title="Queues that need action before anything else."
                    description="The first job is triage: renewals drifting into grace, payments misfiring, and device links going stale."
                />
                <WorkspacePanel
                    title="Operations queue"
                    description="Renewals, payment issues, and device failures in one operator table."
                    contentClassName="p-0"
                >
                    <TableShell>
                        <table className="w-full min-w-[920px] text-sm">
                            <TableHeader labels={['Queue', 'User', 'Status', 'Date / timing', 'Value / provider', 'Details']} />
                            {admin.expiringMembershipWatchlist.length === 0 &&
                            admin.paymentAttentionQueue.length === 0 &&
                            admin.deviceAttentionQueue.length === 0 ? (
                                <EmptyRows message="No urgent operations records are queued right now." colSpan={6} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {admin.expiringMembershipWatchlist.map((entry) => (
                                        <tr key={`renewal-${entry.userName}-${entry.planName}`} className="align-top">
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">Renewal</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{entry.userName}</p>
                                                <p className="mt-1 text-xs text-stone-500">{entry.planName}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                <p>{entry.endsAt ?? 'No end date'}</p>
                                                <p className="mt-1 text-xs text-stone-500">{formatDays(entry.daysRemaining)}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">Membership access</td>
                                            <td className="px-4 py-4 text-stone-600">Confirm renewal path before access drops.</td>
                                        </tr>
                                    ))}
                                    {admin.paymentAttentionQueue.map((entry) => (
                                        <tr key={`payment-${entry.userName}-${entry.reference ?? entry.eventAt ?? entry.planName}`} className="align-top">
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">Payment</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{entry.userName}</p>
                                                <p className="mt-1 text-xs text-stone-500">{entry.planName}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{entry.eventAt ?? 'No event time'}</td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.amount === null
                                                    ? 'No amount'
                                                    : new Intl.NumberFormat('en-US', {
                                                          style: 'currency',
                                                          currency: entry.currency,
                                                          maximumFractionDigits: 0,
                                                      }).format(entry.amount)}
                                                <p className="mt-1 text-xs text-stone-500">{entry.reference ?? 'No reference'}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{entry.notes ?? 'No operator note attached.'}</td>
                                        </tr>
                                    ))}
                                    {admin.deviceAttentionQueue.map((entry) => (
                                        <tr key={`device-${entry.userName}-${entry.provider}`} className="align-top">
                                            <td className="px-4 py-4">
                                                <Badge variant="outline">Device</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{entry.userName}</p>
                                                <p className="mt-1 text-xs text-stone-500">{entry.provider}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(entry.status)}>{humanizeStatus(entry.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{entry.lastSyncedAt ?? 'Never synced'}</td>
                                            <td className="px-4 py-4 text-stone-600">{entry.provider}</td>
                                            <td className="px-4 py-4 text-stone-600">Reconnect or review sync health before metrics go blind.</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </table>
                    </TableShell>
                </WorkspacePanel>
            </div>

            <div className="space-y-4">
                <WorkspaceSectionHeading
                    eyebrow="Next actions"
                    title="Open the right workspace without thinking about it."
                    description="Use these as the operator jump points. The low-value explanation lives after the queues, where it belongs."
                />
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <WorkspaceActionCard
                        title="Control center"
                        href="/admin/control-center"
                        note="Full operations board and playbook."
                        icon={Shield}
                    />
                    <WorkspaceActionCard
                        title="Memberships"
                        href="/memberships"
                        note="Renewals, billing states, and payment events."
                        icon={CreditCard}
                    />
                    <WorkspaceActionCard
                        title="Wearables"
                        href="/wearables"
                        note="Integration health, WHOOP status, and ingest visibility."
                        icon={Watch}
                    />
                    <WorkspaceActionCard title="Users" href="/admin/users" note="Roles, signup channels, and account cleanup." icon={Users} />
                    <WorkspaceActionCard title="Audit log" href="/admin/audit-log" note="Who changed what, when, and from where." icon={FileClock} />
                    <WorkspaceActionCard
                        title="Email logs"
                        href="/admin/email-logs"
                        note="Password reset and workflow delivery trail."
                        icon={MailCheck}
                    />
                </div>
                <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <WorkspacePanel
                        title="Signup mix"
                        description="Current account channel usage and what is still staged for later."
                        contentClassName="p-0"
                    >
                        <TableShell>
                            <table className="w-full min-w-[560px] text-sm">
                                <TableHeader labels={['Method', 'State', 'Accounts']} />
                                <tbody className="divide-y divide-stone-200">
                                    {admin.signupMix.map((entry) => (
                                        <tr key={entry.method}>
                                            <td className="px-4 py-4 font-medium text-stone-950">{entry.label}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant={entry.enabled ? 'default' : 'secondary'}>{entry.enabled ? 'Live' : 'Later'}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{entry.count} account(s)</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableShell>
                    </WorkspacePanel>

                    <WorkspacePanel
                        title="Platform reference"
                        description="Useful context, but not urgent enough to sit above the queues."
                        contentClassName="p-0"
                    >
                        <TableShell>
                            <table className="w-full min-w-[560px] text-sm">
                                <TableHeader labels={['Metric', 'Value', 'Meaning']} />
                                <tbody className="divide-y divide-stone-200">
                                    <tr>
                                        <td className="px-4 py-4 font-medium text-stone-950">Users added this month</td>
                                        <td className="px-4 py-4 text-stone-900">{admin.metrics.newUsersThisMonth}</td>
                                        <td className="px-4 py-4 text-stone-600">Fresh signup activity.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-4 font-medium text-stone-950">Active programs</td>
                                        <td className="px-4 py-4 text-stone-900">{admin.metrics.activePrograms}</td>
                                        <td className="px-4 py-4 text-stone-600">Training programs currently live.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-4 font-medium text-stone-950">Scheduled this week</td>
                                        <td className="px-4 py-4 text-stone-900">{admin.metrics.scheduledSessionsThisWeek}</td>
                                        <td className="px-4 py-4 text-stone-600">Sessions planned in the next seven days.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-4 font-medium text-stone-950">Logged this week</td>
                                        <td className="px-4 py-4 text-stone-900">{admin.metrics.loggedSessionsThisWeek}</td>
                                        <td className="px-4 py-4 text-stone-600">Workout logs completed this week.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableShell>
                    </WorkspacePanel>
                </div>
            </div>
        </section>
    );
}
