import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CoachAppShell } from '@/components/coach-app-shell';
import { WorkspacePanel, WorkspaceTable, WorkspaceTableEmpty, WorkspaceTableHeader } from '@/components/workspace-primitives';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, CheckCircle2, Dumbbell, MailPlus, MessageCircle, type LucideIcon, Users } from 'lucide-react';
import { type ReactNode } from 'react';

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

function SummaryTile({ label, value, note, icon: Icon }: { label: string; value: string; note: string; icon: LucideIcon }) {
    return (
        <div className="rounded-[1.35rem] border border-stone-200 bg-white p-4 shadow-[0_18px_38px_-34px_rgba(15,23,42,0.35)]">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">{label}</p>
                    <p className="mt-2 text-3xl font-black tracking-[-0.06em] text-stone-950">{value}</p>
                </div>
                <span className="grid size-10 place-items-center rounded-2xl bg-amber-100 text-amber-800">
                    <Icon className="size-5" />
                </span>
            </div>
            <p className="mt-3 text-sm leading-6 text-stone-600">{note}</p>
        </div>
    );
}

function MobileSection({ title, description, children }: { title: string; description: string; children: ReactNode }) {
    return (
        <section className="space-y-3 md:hidden">
            <div>
                <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">{title}</p>
                <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p>
            </div>
            {children}
        </section>
    );
}

function EmptyMobileState({ message }: { message: string }) {
    return <div className="rounded-[1.25rem] border border-dashed border-stone-300 bg-white p-4 text-sm leading-6 text-stone-600">{message}</div>;
}

export default function CoachAppIndex({ viewer, summary, athletes, programs, schedule, pendingLogs }: CoachAppProps) {
    return (
        <CoachAppShell active="home" unreadMessages={summary.unreadMessages}>
            <Head title="Coach app" />

            <div className="mx-auto max-w-6xl space-y-6 px-4 py-5 md:px-6 md:py-8">
                <section className="overflow-hidden rounded-[2rem] border border-amber-200 bg-gradient-to-br from-amber-100 via-white to-stone-50 p-5 shadow-[0_24px_60px_-45px_rgba(120,53,15,0.5)] md:p-8">
                    <Badge className="rounded-full bg-stone-950 text-white hover:bg-stone-950">Coach app</Badge>
                    <h1 className="mt-4 font-['Space_Grotesk'] text-3xl leading-tight font-bold tracking-[-0.06em] text-stone-950 sm:text-5xl">
                        Today’s coaching board
                    </h1>
                    <p className="mt-3 max-w-2xl text-sm leading-7 text-stone-700">
                        {viewer.name}, this is your app home: athletes, schedule, programs, messages, and missing logs. Admin controls stay in the backend.
                    </p>
                    <div className="mt-5 grid gap-2 sm:flex sm:flex-wrap">
                        <Button asChild className="h-12 rounded-2xl bg-amber-500 text-stone-950 hover:bg-amber-400">
                            <Link href="/training">
                                Open programs
                                <Dumbbell className="size-4" />
                            </Link>
                        </Button>
                        <Button asChild variant="outline" className="h-12 rounded-2xl border-stone-300 bg-white">
                            <Link href="/roster/invites">
                                Invite athlete
                                <MailPlus className="size-4" />
                            </Link>
                        </Button>
                        <Button asChild variant="outline" className="h-12 rounded-2xl border-stone-300 bg-white">
                            <Link href="/messages">
                                Messages
                                <MessageCircle className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </section>

                <section className="grid grid-cols-2 gap-3 lg:grid-cols-5">
                    <SummaryTile label="Athletes" value={String(summary.assignedAthletes)} note="Active relationships." icon={Users} />
                    <SummaryTile label="Programs" value={String(summary.activePrograms)} note="Live blocks." icon={Dumbbell} />
                    <SummaryTile label="Schedule" value={String(summary.upcomingSessions)} note="Upcoming sessions." icon={CalendarDays} />
                    <SummaryTile label="Missing" value={String(summary.pendingLogs)} note="Needs follow-up." icon={CheckCircle2} />
                    <SummaryTile label="Messages" value={String(summary.unreadMessages)} note="Waiting replies." icon={MessageCircle} />
                </section>

                <MobileSection title="Schedule" description="Upcoming sessions from your programs. Tap athlete names for detail.">
                    {schedule.length === 0 ? (
                        <EmptyMobileState message="No sessions scheduled over the next 14 days." />
                    ) : (
                        <div className="space-y-3" id="schedule">
                            {schedule.slice(0, 8).map((session) => (
                                <article key={session.id} className="rounded-[1.35rem] border border-stone-200 bg-white p-4 shadow-[0_18px_38px_-34px_rgba(15,23,42,0.35)]">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-stone-400 uppercase">{formatDate(session.scheduledDate)}</p>
                                            <p className="mt-1 font-semibold text-stone-950">{session.title}</p>
                                            <Link href={route('athletes.show', session.athlete.id)} className="mt-1 inline-flex text-sm font-semibold text-amber-800">
                                                {session.athlete.name}
                                            </Link>
                                        </div>
                                        <Badge variant={badgeVariant(session.completionStatus)}>{humanize(session.completionStatus)}</Badge>
                                    </div>
                                    <p className="mt-3 text-sm leading-6 text-stone-600">{session.exercisePreview.join(', ') || session.focus || 'No exercises entered.'}</p>
                                    <div className="mt-3 flex items-center justify-between text-sm text-stone-500">
                                        <span>{session.program.title}</span>
                                        <span>{session.mediaCount} media</span>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                </MobileSection>

                <MobileSection title="Athletes" description="Assigned athletes and what needs attention.">
                    {athletes.length === 0 ? (
                        <EmptyMobileState message="No athletes are assigned yet." />
                    ) : (
                        <div className="space-y-3">
                            {athletes.map((athlete) => (
                                <article key={athlete.id} className="rounded-[1.35rem] border border-stone-200 bg-white p-4 shadow-[0_18px_38px_-34px_rgba(15,23,42,0.35)]">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <Link href={route('athletes.show', athlete.id)} className="font-semibold text-stone-950">
                                                {athlete.name}
                                            </Link>
                                            <p className="mt-1 text-sm text-stone-500">{athlete.email}</p>
                                        </div>
                                        <Badge variant={athlete.membershipNeedsAttention ? 'destructive' : 'outline'}>{humanize(athlete.membershipStatus)}</Badge>
                                    </div>
                                    <p className="mt-3 text-sm leading-6 text-stone-600">{athlete.goal ?? 'No goal set.'}</p>
                                    <div className="mt-3 grid grid-cols-2 gap-2 text-sm">
                                        <div className="rounded-2xl bg-stone-50 p-3">
                                            <span className="block text-stone-400">Devices</span>
                                            <span className="font-semibold text-stone-900">{athlete.connectedDevices}</span>
                                        </div>
                                        <div className="rounded-2xl bg-stone-50 p-3">
                                            <span className="block text-stone-400">Check-in</span>
                                            <span className="font-semibold text-stone-900">{formatDate(athlete.latestCheckInAt)}</span>
                                        </div>
                                    </div>
                                    <Button asChild variant="outline" className="mt-3 h-11 w-full rounded-2xl">
                                        <Link href={route('athletes.show', athlete.id)}>
                                            Open profile
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                </article>
                            ))}
                        </div>
                    )}
                </MobileSection>

                <WorkspacePanel title="Assigned athletes" description="Only athletes actively assigned to you are shown here." contentClassName="p-0" className="hidden md:block">
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

                <div id="schedule" className="hidden scroll-mt-8 md:block">
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

                <MobileSection title="Programs" description="Active and draft blocks you own.">
                    {programs.length === 0 ? (
                        <EmptyMobileState message="No programs found." />
                    ) : (
                        <div className="space-y-3">
                            {programs.slice(0, 8).map((program) => (
                                <article key={program.id} className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-stone-950">{program.title}</p>
                                            <p className="mt-1 text-sm text-stone-600">{program.athlete.name}</p>
                                        </div>
                                        <Badge variant={badgeVariant(program.status)}>{humanize(program.status)}</Badge>
                                    </div>
                                    <p className="mt-3 text-sm text-stone-600">{program.completedSessionCount}/{program.sessionCount} sessions complete</p>
                                    <p className="mt-1 text-sm text-stone-500">Next: {formatDate(program.nextSessionDate)}</p>
                                </article>
                            ))}
                        </div>
                    )}
                </MobileSection>

                <MobileSection title="Pending logs" description="Past sessions still missing athlete execution data.">
                    {pendingLogs.length === 0 ? (
                        <EmptyMobileState message="No pending logs. Clean." />
                    ) : (
                        <div className="space-y-3">
                            {pendingLogs.map((log) => (
                                <article key={log.id} className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                                    <p className="text-xs font-semibold tracking-[0.16em] text-stone-400 uppercase">{formatDate(log.scheduledDate)}</p>
                                    <p className="mt-1 font-semibold text-stone-950">{log.title}</p>
                                    <Link href={route('athletes.show', log.athleteId)} className="mt-2 inline-flex text-sm font-semibold text-amber-800">
                                        {log.athleteName}
                                    </Link>
                                    <p className="mt-2 text-sm text-stone-600">{log.programTitle}</p>
                                </article>
                            ))}
                        </div>
                    )}
                </MobileSection>

                <section className="hidden gap-6 md:grid xl:grid-cols-2">
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
        </CoachAppShell>
    );
}
