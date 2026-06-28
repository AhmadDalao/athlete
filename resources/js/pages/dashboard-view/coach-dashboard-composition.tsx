import { Badge } from '@/components/ui/badge';
import {
    WorkspaceActionCard,
    WorkspaceMetricCard,
    WorkspacePanel,
    WorkspaceSectionHeading,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
} from '@/components/workspace-primitives';
import {
    badgeVariantForPriority,
    badgeVariantForStatus,
    formatCalories,
    formatDays,
    formatGrams,
    formatLiters,
    formatReadiness,
    formatSleepHours,
    formatWeight,
    humanizeStatus,
} from '@/pages/dashboard-view/helpers';
import { type CoachOverview } from '@/pages/dashboard-view/types';
import { Activity, ClipboardList, Dumbbell, LineChart, Users, Watch } from 'lucide-react';

export function CoachDashboardComposition({ coach }: { coach: CoachOverview }) {
    const missingSignals = coach.roster.filter(
        (entry) => entry.flags.length > 0 || entry.connectedDevices === 0 || !entry.currentProgram || !entry.latestSnapshot || !entry.latestCheckIn,
    );

    return (
        <section id="coach-board" className="space-y-6">
            <WorkspaceSectionHeading
                eyebrow="Coach board"
                title="Make the next coaching decision fast."
                description="This board answers who needs attention, what is scheduled, what is missing, and what page you should open next."
            />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <WorkspaceMetricCard
                    title="Roster size"
                    value={coach.metrics.rosterCount.toString()}
                    note="Athletes currently assigned to this coaching scope."
                    icon={Users}
                />
                <WorkspaceMetricCard
                    title="Needs attention"
                    value={coach.metrics.athletesNeedingAttention.toString()}
                    note="Athletes with enough warning signs that you should not ignore them."
                    icon={ClipboardList}
                />
                <WorkspaceMetricCard
                    title="Upcoming sessions"
                    value={coach.metrics.upcomingSessions.toString()}
                    note="Scheduled sessions waiting in the next seven days."
                    icon={Activity}
                />
                <WorkspaceMetricCard
                    title="Pending workout logs"
                    value={coach.metrics.pendingWorkoutLogs.toString()}
                    note="Past-due athlete follow-through still missing from the loop."
                    icon={LineChart}
                />
                <WorkspaceMetricCard
                    title="Active programs"
                    value={coach.metrics.activePrograms.toString()}
                    note="Blocks currently carrying real workload."
                    icon={Dumbbell}
                />
                <WorkspaceMetricCard
                    title="Missing coverage"
                    value={missingSignals.length.toString()}
                    note="Roster entries missing device signal, a program, or a fresh check-in."
                    icon={Watch}
                />
            </div>

            <div className="space-y-4">
                <WorkspaceSectionHeading
                    eyebrow="Urgent"
                    title="Start with the people and sessions most likely to drift."
                    description="This is the decision board: the roster pressure points first, supporting detail second."
                />
                <div className="grid gap-4 xl:grid-cols-3">
                    <WorkspacePanel
                        title="Who needs attention"
                        description="The athletes most likely to require a direct coaching move next."
                        contentClassName="p-0"
                    >
                        <WorkspaceTable minWidth="min-w-[760px]">
                            <WorkspaceTableHeader labels={['Athlete', 'Goal', 'Program', 'Flags', 'Next move']} />
                            {coach.attentionQueue.length === 0 ? (
                                <WorkspaceTableEmpty message="Nobody is waving a red flag right now." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {coach.attentionQueue.map((entry) => (
                                        <tr key={entry.id} className="align-top">
                                            <td className="px-4 py-4 font-medium text-stone-950">{entry.name}</td>
                                            <td className="px-4 py-4 text-stone-600">{entry.goal}</td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.currentProgram ? (
                                                    <>
                                                        <p>{entry.currentProgram.title}</p>
                                                        <p className="mt-1 text-xs text-stone-500">
                                                            {entry.currentProgram.pendingLogs} pending log(s)
                                                        </p>
                                                    </>
                                                ) : (
                                                    'No active program'
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    {entry.flags.length === 0 ? (
                                                        <Badge variant="default">Stable</Badge>
                                                    ) : (
                                                        entry.flags.map((flag) => (
                                                            <Badge key={flag} variant="secondary">
                                                                {flag}
                                                            </Badge>
                                                        ))
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.currentProgram ? 'Review log and next session.' : 'Assign a training program.'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <WorkspacePanel
                        title="What is scheduled"
                        description="The sessions that are about to hit the calendar next."
                        contentClassName="p-0"
                    >
                        <WorkspaceTable minWidth="min-w-[720px]">
                            <WorkspaceTableHeader labels={['Session', 'Athlete', 'Program', 'Date', 'Focus']} />
                            {coach.upcomingSessions.length === 0 ? (
                                <WorkspaceTableEmpty message="Nothing is scheduled in the next week." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {coach.upcomingSessions.map((session) => (
                                        <tr key={`${session.athleteName}-${session.sessionTitle}`} className="align-top">
                                            <td className="px-4 py-4 font-medium text-stone-950">{session.sessionTitle}</td>
                                            <td className="px-4 py-4 text-stone-600">{session.athleteName}</td>
                                            <td className="px-4 py-4 text-stone-600">{session.programTitle}</td>
                                            <td className="px-4 py-4 text-stone-600">{session.scheduledDate ?? 'No date'}</td>
                                            <td className="px-4 py-4 text-stone-600">{session.focus ?? 'No focus set'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <WorkspacePanel
                        title="What is missing"
                        description="Coverage gaps across device signal, check-ins, and assigned work."
                        contentClassName="p-0"
                    >
                        <WorkspaceTable minWidth="min-w-[820px]">
                            <WorkspaceTableHeader labels={['Athlete', 'Membership', 'Devices', 'Program', 'Recovery', 'Check-in', 'Flags']} />
                            {missingSignals.length === 0 ? (
                                <WorkspaceTableEmpty message="The roster is unusually clean right now." colSpan={7} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {missingSignals.slice(0, 6).map((entry) => (
                                        <tr key={entry.id} className="align-top">
                                            <td className="px-4 py-4 font-medium text-stone-950">{entry.name}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(entry.membershipStatus)}>
                                                    {humanizeStatus(entry.membershipStatus)}
                                                </Badge>
                                                <p className="mt-1 text-xs text-stone-500">{formatDays(entry.daysRemaining)}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{entry.connectedDevices} device(s)</td>
                                            <td className="px-4 py-4 text-stone-600">{entry.currentProgram?.title ?? 'Missing'}</td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.latestSnapshot ? formatReadiness(entry.latestSnapshot.readinessScore) : 'Missing'}
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.latestCheckIn ? entry.latestCheckIn.loggedDate ?? 'Logged' : 'Missing'}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    {entry.connectedDevices === 0 && <Badge variant="secondary">No device</Badge>}
                                                    {!entry.currentProgram && <Badge variant="secondary">No active program</Badge>}
                                                    {!entry.latestSnapshot && <Badge variant="secondary">No recovery snapshot</Badge>}
                                                    {!entry.latestCheckIn && <Badge variant="secondary">No recent check-in</Badge>}
                                                    {entry.flags.map((flag) => (
                                                        <Badge key={flag} variant="secondary">
                                                            {flag}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </div>
            </div>

            <div className="space-y-4">
                <WorkspaceSectionHeading
                    eyebrow="Reference"
                    title="Use the quick paths, then inspect the roster detail."
                    description="This is the lower-priority layer: open the right workspace next and keep a compact roster read below it."
                />
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceActionCard
                        title="Roster"
                        href="/roster"
                        note="Assignments, coverage, and direct coach-athlete relationships."
                        icon={Users}
                    />
                    <WorkspaceActionCard title="Training" href="/training" note="Programs, sessions, and workout logs." icon={Dumbbell} />
                    <WorkspaceActionCard
                        title="Progress"
                        href="/progress"
                        note="Weight, nutrition, hydration, and manual check-ins."
                        icon={LineChart}
                    />
                    <WorkspaceActionCard title="Wearables" href="/wearables" note="Device health and recovery context." icon={Watch} />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                    <WorkspacePanel
                        title="Roster reference"
                        description="A compact roster read with membership, recovery, and program context."
                        contentClassName="p-0"
                    >
                        <WorkspaceTable minWidth="min-w-[980px]">
                            <WorkspaceTableHeader labels={['Athlete', 'Membership', 'Program', 'Recovery', 'Food / body', 'Flags']} />
                            {coach.roster.length === 0 ? (
                                <WorkspaceTableEmpty message="No athletes are assigned yet." colSpan={6} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {coach.roster.map((entry) => (
                                        <tr key={entry.id} className="align-top">
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{entry.name}</p>
                                                <p className="mt-1 text-xs text-stone-500">{entry.email}</p>
                                                <p className="mt-2 max-w-[220px] text-xs leading-5 text-stone-500">{entry.goal}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForStatus(entry.membershipStatus)}>
                                                    {humanizeStatus(entry.membershipStatus)}
                                                </Badge>
                                                <p className="mt-2 text-xs text-stone-500">{entry.membershipPlan}</p>
                                                <p className="text-xs text-stone-500">{formatDays(entry.daysRemaining)}</p>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.currentProgram ? (
                                                    <>
                                                        <p>{entry.currentProgram.title}</p>
                                                        <p className="mt-1 text-xs text-stone-500">
                                                            Next {entry.currentProgram.nextSessionDate ?? 'not scheduled'}
                                                        </p>
                                                        <p className="text-xs text-stone-500">{entry.currentProgram.pendingLogs} pending log(s)</p>
                                                    </>
                                                ) : (
                                                    'No current program'
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.latestSnapshot ? (
                                                    <>
                                                        <p>{formatReadiness(entry.latestSnapshot.readinessScore)}</p>
                                                        <p className="mt-1 text-xs text-stone-500">
                                                            {formatSleepHours(entry.latestSnapshot.sleepHours)} sleep · strain{' '}
                                                            {entry.latestSnapshot.strainScore ?? 'N/A'}
                                                        </p>
                                                    </>
                                                ) : (
                                                    'No recovery snapshot'
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">
                                                {entry.latestCheckIn ? (
                                                    <>
                                                        <p>{formatWeight(entry.latestCheckIn.weightKg)}</p>
                                                        <p className="mt-1 text-xs text-stone-500">
                                                            {formatCalories(entry.latestCheckIn.caloriesConsumed)} ·{' '}
                                                            {formatGrams(entry.latestCheckIn.proteinGrams)} protein
                                                        </p>
                                                        <p className="text-xs text-stone-500">{formatLiters(entry.latestCheckIn.waterLiters)} water</p>
                                                    </>
                                                ) : (
                                                    'No recent check-in'
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    {entry.flags.length === 0 ? (
                                                        <Badge variant="default">Stable</Badge>
                                                    ) : (
                                                        entry.flags.map((flag) => (
                                                            <Badge key={flag} variant="secondary">
                                                                {flag}
                                                            </Badge>
                                                        ))
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <WorkspacePanel
                        title="Weekly briefs"
                        description="The short version of why an athlete deserves a decision, not just a glance."
                        contentClassName="p-0"
                    >
                        <WorkspaceTable minWidth="min-w-[820px]">
                            <WorkspaceTableHeader labels={['Athlete', 'Priority', 'Headline', 'Reasons', 'Summary']} />
                            {coach.weeklyBriefs.length === 0 ? (
                                <WorkspaceTableEmpty message="No briefs exist yet because there is not enough athlete signal to summarize." colSpan={5} />
                            ) : (
                                <tbody className="divide-y divide-stone-200">
                                    {coach.weeklyBriefs.map((entry) => (
                                        <tr key={entry.id} className="align-top">
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-stone-950">{entry.name}</p>
                                                <p className="mt-1 max-w-[220px] text-xs leading-5 text-stone-500">{entry.goal}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={badgeVariantForPriority(entry.weeklyBrief.priority)}>
                                                    {humanizeStatus(entry.weeklyBrief.priority)}
                                                </Badge>
                                                <p className="mt-2 text-xs text-stone-500">Score {entry.weeklyBrief.score}</p>
                                            </td>
                                            <td className="px-4 py-4 font-medium text-stone-900">{entry.weeklyBrief.headline}</td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    {entry.weeklyBrief.reasons.map((reason) => (
                                                        <Badge key={reason} variant="secondary">
                                                            {reason}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4 text-stone-600">{entry.weeklyBrief.summary}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </div>
            </div>
        </section>
    );
}
