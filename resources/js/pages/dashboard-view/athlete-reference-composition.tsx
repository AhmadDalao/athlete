import { Badge } from '@/components/ui/badge';
import { WorkspaceMetricCard, WorkspacePanel, WorkspaceSectionHeading } from '@/components/workspace-primitives';
import { badgeVariantForStatus, formatDays, formatReadiness, formatSleepHours, humanizeStatus } from '@/pages/dashboard-view/helpers';
import { type AthleteOverview } from '@/pages/dashboard-view/types';
import { Activity, CreditCard, HeartPulse, Watch } from 'lucide-react';

export function AthleteReferenceComposition({ athlete }: { athlete: AthleteOverview }) {
    return (
        <section id="athlete-reference" className="space-y-6">
            <WorkspaceSectionHeading
                eyebrow="Athlete reference"
                title="Keep the athlete signal visible without turning this page into three dashboards fighting each other."
                description="This stays compact for mixed-role accounts: membership runway, current training, coach coverage, and recovery signal."
            />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <WorkspaceMetricCard
                    title="Days remaining"
                    value={athlete.metrics.membershipDaysRemaining === null ? 'N/A' : athlete.metrics.membershipDaysRemaining.toString()}
                    note={`Current access is ${humanizeStatus(athlete.membership.status)}.`}
                    icon={CreditCard}
                />
                <WorkspaceMetricCard
                    title="Latest readiness"
                    value={formatReadiness(athlete.metrics.latestReadinessScore)}
                    note={`${athlete.metrics.connectedDevices} connected device(s).`}
                    icon={HeartPulse}
                />
                <WorkspaceMetricCard
                    title="Upcoming sessions"
                    value={athlete.metrics.upcomingSessionsCount.toString()}
                    note={`${athlete.metrics.completedSessionsThisWeek} completed this week.`}
                    icon={Activity}
                />
                <WorkspaceMetricCard
                    title="Coach coverage"
                    value={athlete.metrics.coachCount.toString()}
                    note="Active coach relationships tied to this athlete record."
                    icon={Watch}
                />
            </div>

            <div className="grid gap-4 xl:grid-cols-[0.92fr_1.08fr]">
                <WorkspacePanel
                    title="Membership and coaches"
                    description="The support basics: time left, renewal state, and assigned coaches."
                    contentClassName="space-y-4"
                >
                    <div className="flex flex-wrap gap-2">
                        <Badge variant={badgeVariantForStatus(athlete.membership.status)}>{humanizeStatus(athlete.membership.status)}</Badge>
                        <Badge variant="outline">{athlete.membership.planName}</Badge>
                        <Badge variant="outline">{formatDays(athlete.membership.daysRemaining)}</Badge>
                    </div>
                    <div className="grid gap-3">
                        {athlete.coaches.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-stone-200/80 p-4 text-sm leading-6 text-stone-500">
                                No coach is assigned yet.
                            </div>
                        ) : (
                            athlete.coaches.map((coachEntry) => (
                                <div key={coachEntry.id} className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="font-medium text-stone-950">{coachEntry.name}</p>
                                            <p className="text-sm text-stone-500">{coachEntry.email}</p>
                                        </div>
                                        <Badge variant="outline">{humanizeStatus(coachEntry.relationshipStatus)}</Badge>
                                    </div>
                                    <p className="mt-2 text-sm leading-6 text-stone-600">{coachEntry.goal ?? 'General coaching relationship'}</p>
                                </div>
                            ))
                        )}
                    </div>
                </WorkspacePanel>

                <WorkspacePanel
                    title="Training and recovery"
                    description="The athlete snapshot that still matters in a mixed-role account."
                    contentClassName="space-y-4"
                >
                    <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p className="font-medium text-stone-950">{athlete.training?.title ?? 'No active block'}</p>
                                <p className="text-sm text-stone-500">{athlete.training?.nextSession?.title ?? 'No next session scheduled'}</p>
                            </div>
                            {athlete.training && (
                                <Badge variant={badgeVariantForStatus(athlete.training.status)}>{humanizeStatus(athlete.training.status)}</Badge>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                            <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Latest sleep</p>
                            <p className="mt-2 text-lg font-semibold text-stone-950">
                                {formatSleepHours(athlete.latestSnapshot?.sleepHours ?? null)}
                            </p>
                        </div>
                        <div className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4">
                            <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Device signal</p>
                            <p className="mt-2 text-lg font-semibold text-stone-950">{athlete.metrics.connectedDevices} device(s)</p>
                        </div>
                    </div>
                    <div className="grid gap-3">
                        {athlete.deviceConnections.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-stone-200/80 p-4 text-sm leading-6 text-stone-500">
                                No connected wearables yet.
                            </div>
                        ) : (
                            athlete.deviceConnections.map((connection) => (
                                <div
                                    key={`${connection.provider}-${connection.lastSyncedAt ?? 'never'}`}
                                    className="rounded-2xl border border-stone-200/75 bg-stone-50/80 p-4"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="font-medium text-stone-950">{connection.providerLabel}</p>
                                            <p className="text-sm text-stone-500">{connection.lastSyncedAt ?? 'Never synced'}</p>
                                        </div>
                                        <Badge variant={badgeVariantForStatus(connection.status)}>{humanizeStatus(connection.status)}</Badge>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </WorkspacePanel>
            </div>
        </section>
    );
}
