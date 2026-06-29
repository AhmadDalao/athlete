import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    WorkspaceHero,
    WorkspaceMetricCard,
    WorkspacePanel,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
} from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, Dumbbell, MailPlus, MessageCircle, Users } from 'lucide-react';

interface CoachAppProps {
    viewer: {
        id: number;
        name: string;
        email: string;
    };
    summary: {
        assignedAthletes: number;
        activePrograms: number;
        upcomingSessions: number;
        pendingLogs: number;
        unreadMessages: number;
    };
    athletes: Array<{
        id: number;
        name: string;
        email: string;
        goal: string | null;
        startedAt: string | null;
        membershipStatus: string;
        membershipNeedsAttention: boolean;
        latestCheckInAt: string | null;
        connectedDevices: number;
        currentProgram: {
            id: number;
            title: string;
            status: string;
        } | null;
    }>;
    programs: Array<{
        id: number;
        title: string;
        goal: string | null;
        status: string;
        startDate: string | null;
        endDate: string | null;
        athlete: {
            id: number;
            name: string;
            email: string;
        };
        sessionCount: number;
        completedSessionCount: number;
        pendingSessionCount: number;
        nextSessionDate: string | null;
    }>;
    schedule: Array<{
        id: number;
        title: string;
        scheduledDate: string | null;
        focus: string | null;
        mediaCount: number;
        exercisePreview: string[];
        completionStatus: string;
        program: {
            id: number;
            title: string;
        };
        athlete: {
            id: number;
            name: string;
            email: string;
        };
    }>;
    pendingLogs: Array<{
        id: number;
        title: string;
        scheduledDate: string | null;
        focus: string | null;
        programTitle: string;
        athleteId: number;
        athleteName: string;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Coach app',
        href: '/coach',
    },
];

function formatDate(value: string | null) {
    if (!value) {
        return 'Not scheduled';
    }

    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}

function humanize(value: string | null | undefined) {
    return value ? value.replace(/_/g, ' ') : 'none';
}

type BadgeVariant = 'default' | 'destructive' | 'outline' | 'secondary';

function badgeVariant(status: string): BadgeVariant {
    if (['active', 'completed'].includes(status)) {
        return 'default';
    }

    if (['past_due', 'missed', 'pending'].includes(status)) {
        return 'destructive';
    }

    return 'outline';
}

export default function CoachAppIndex({ viewer, summary, athletes, programs, schedule, pendingLogs }: CoachAppProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Coach app" />

            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow="Coach app"
                    title={`Coach home, ${viewer.name}`}
                    description="Your athletes, assigned programs, schedule, messages, and missed logs without entering the business admin dashboard."
                    badges={[`${summary.assignedAthletes} athletes`, `${summary.activePrograms} active programs`]}
                    actions={
                        <>
                            <Button asChild className="bg-amber-500 text-stone-950 hover:bg-amber-400">
                                <Link href="/training">
                                    Open programs
                                    <Dumbbell className="size-4" />
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/roster/invites">
                                    Invite athlete
                                    <MailPlus className="size-4" />
                                </Link>
                            </Button>
                        </>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <WorkspaceMetricCard title="Assigned athletes" value={String(summary.assignedAthletes)} note="Active coach-athlete relationships." icon={Users} />
                    <WorkspaceMetricCard title="Active programs" value={String(summary.activePrograms)} note="Live blocks owned by you." icon={Dumbbell} />
                    <WorkspaceMetricCard title="Next 14 days" value={String(summary.upcomingSessions)} note="Scheduled sessions coming up." icon={CalendarDays} />
                    <WorkspaceMetricCard title="Pending logs" value={String(summary.pendingLogs)} note="Past sessions missing execution data." icon={CheckCircle2} />
                    <WorkspaceMetricCard title="Unread messages" value={String(summary.unreadMessages)} note="Athlete messages waiting." icon={MessageCircle} />
                </section>

                <WorkspacePanel title="Assigned athletes" description="Only athletes actively assigned to you are shown here." contentClassName="p-0">
                    <WorkspaceTable minWidth="min-w-[960px]">
                        <WorkspaceTableHeader labels={['Athlete', 'Goal', 'Membership', 'Devices', 'Check-in', 'Program', 'Actions']} />
                        {athletes.length === 0 ? (
                            <WorkspaceTableEmpty message="No athletes are assigned yet." colSpan={7} />
                        ) : (
                            <tbody>
                                {athletes.map((athlete) => (
                                    <tr key={athlete.id} className="border-t border-stone-100 align-top">
                                        <td className="px-5 py-4">
                                            <Link href={route('athletes.show', athlete.id)} className="font-black text-stone-950 hover:text-amber-700">
                                                {athlete.name}
                                            </Link>
                                            <p className="mt-1 text-sm text-stone-500">{athlete.email}</p>
                                        </td>
                                        <td className="px-5 py-4 text-sm text-stone-600">{athlete.goal ?? 'No goal set'}</td>
                                        <td className="px-5 py-4">
                                            <Badge variant={athlete.membershipNeedsAttention ? 'destructive' : 'outline'}>{humanize(athlete.membershipStatus)}</Badge>
                                        </td>
                                        <td className="px-5 py-4 text-sm text-stone-600">{athlete.connectedDevices}</td>
                                        <td className="px-5 py-4 text-sm text-stone-600">{formatDate(athlete.latestCheckInAt)}</td>
                                        <td className="px-5 py-4">
                                            {athlete.currentProgram ? (
                                                <>
                                                    <p className="font-medium text-stone-950">{athlete.currentProgram.title}</p>
                                                    <Badge variant={badgeVariant(athlete.currentProgram.status)}>{humanize(athlete.currentProgram.status)}</Badge>
                                                </>
                                            ) : (
                                                <span className="text-sm text-stone-500">No active program</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex flex-wrap gap-2">
                                                <Button asChild size="sm" variant="outline">
                                                    <Link href={route('athletes.show', athlete.id)}>Profile</Link>
                                                </Button>
                                                <Button asChild size="sm" variant="ghost">
                                                    <Link href="/training">Programs</Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        )}
                    </WorkspaceTable>
                </WorkspacePanel>

                <div id="schedule" className="scroll-mt-8">
                    <WorkspacePanel title="Schedule" description="Upcoming sessions from your own programs only." contentClassName="p-0">
                        <WorkspaceTable minWidth="min-w-[980px]">
                            <WorkspaceTableHeader labels={['Date', 'Athlete', 'Program', 'Workout', 'Exercises', 'Media', 'Status']} />
                            {schedule.length === 0 ? (
                                <WorkspaceTableEmpty message="No sessions scheduled over the next 14 days." colSpan={7} />
                            ) : (
                                <tbody>
                                    {schedule.map((session) => (
                                        <tr key={session.id} className="border-t border-stone-100 align-top">
                                            <td className="px-5 py-4 text-sm text-stone-600">{formatDate(session.scheduledDate)}</td>
                                            <td className="px-5 py-4">
                                                <Link href={route('athletes.show', session.athlete.id)} className="font-medium text-stone-950 hover:text-amber-700">
                                                    {session.athlete.name}
                                                </Link>
                                                <p className="text-xs text-stone-500">{session.athlete.email}</p>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{session.program.title}</td>
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-stone-950">{session.title}</p>
                                                <p className="text-sm text-stone-500">{session.focus ?? 'No focus'}</p>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{session.exercisePreview.join(', ') || 'No exercises'}</td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{session.mediaCount}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={badgeVariant(session.completionStatus)}>{humanize(session.completionStatus)}</Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </div>

                <section className="grid gap-6 xl:grid-cols-2">
                    <WorkspacePanel title="Owned programs" description="Active and draft blocks currently assigned by you." contentClassName="p-0">
                        <WorkspaceTable minWidth="min-w-[780px]">
                            <WorkspaceTableHeader labels={['Program', 'Athlete', 'Sessions', 'Next', 'Status']} />
                            {programs.length === 0 ? (
                                <WorkspaceTableEmpty message="No programs found." colSpan={5} />
                            ) : (
                                <tbody>
                                    {programs.map((program) => (
                                        <tr key={program.id} className="border-t border-stone-100 align-top">
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-stone-950">{program.title}</p>
                                                <p className="text-sm text-stone-500">{program.goal ?? 'No goal set'}</p>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{program.athlete.name}</td>
                                            <td className="px-5 py-4 text-sm text-stone-600">
                                                {program.completedSessionCount}/{program.sessionCount}
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{formatDate(program.nextSessionDate)}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={badgeVariant(program.status)}>{humanize(program.status)}</Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <WorkspacePanel title="Pending logs" description="Past sessions that still need athlete execution data." contentClassName="p-0">
                        <WorkspaceTable minWidth="min-w-[720px]">
                            <WorkspaceTableHeader labels={['Date', 'Athlete', 'Program', 'Session']} />
                            {pendingLogs.length === 0 ? (
                                <WorkspaceTableEmpty message="No pending logs. Clean." colSpan={4} />
                            ) : (
                                <tbody>
                                    {pendingLogs.map((log) => (
                                        <tr key={log.id} className="border-t border-stone-100">
                                            <td className="px-5 py-4 text-sm text-stone-600">{formatDate(log.scheduledDate)}</td>
                                            <td className="px-5 py-4">
                                                <Link href={route('athletes.show', log.athleteId)} className="font-medium text-stone-950 hover:text-amber-700">
                                                    {log.athleteName}
                                                </Link>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{log.programTitle}</td>
                                            <td className="px-5 py-4 text-sm text-stone-600">{log.title}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>
            </div>
        </AppLayout>
    );
}
