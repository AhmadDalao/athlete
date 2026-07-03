import { type AuthenticatedSharedData, type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import { AthleteAppShell } from '@/components/athlete-app-shell';
import { CoachAppShell } from '@/components/coach-app-shell';
import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Clock3, Dumbbell, ListChecks, Medal, TimerReset } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

type PerformanceWindowKey = 'latestSession' | 'sevenDays' | 'thirtyDays';

type PerformanceWindow = {
    label: string;
    sessionTitle?: string | null;
    programTitle?: string | null;
    coachName?: string | null;
    performedAt?: string | null;
    sessionsLogged: number;
    setsCompleted: number;
    totalReps: number;
    tonnage: number;
    durationMinutes: number;
};

type PersonalRecord = {
    exerciseName: string;
    load: number;
    reps?: number | null;
    completedAt?: string | null;
};

type ProfilePerformance = {
    latestSession: PerformanceWindow;
    sevenDays: PerformanceWindow;
    thirtyDays: PerformanceWindow;
    prs: PersonalRecord[];
};

const performanceTabs: Array<{ key: PerformanceWindowKey; label: string }> = [
    { key: 'latestSession', label: 'Latest Session' },
    { key: 'sevenDays', label: 'Last 7 Days' },
    { key: 'thirtyDays', label: 'Last 30 Days' },
];

function compactNumber(value: number): string {
    return new Intl.NumberFormat('en', { maximumFractionDigits: value > 100 ? 0 : 1 }).format(value);
}

function formatDuration(minutes: number): string {
    if (!minutes) {
        return '--';
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return hours > 0 ? `${hours}:${String(remainingMinutes).padStart(2, '0')}` : `${remainingMinutes}m`;
}

function ProfilePerformancePanel({ performance }: { performance?: ProfilePerformance }) {
    const [activeTab, setActiveTab] = useState<PerformanceWindowKey>('latestSession');
    const activeStats = performance?.[activeTab];
    const prs = performance?.prs ?? [];

    if (!performance || !activeStats) {
        return (
            <section className="rounded-[1.65rem] border border-stone-200 bg-white p-5 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)]">
                <p className="text-xs font-semibold tracking-[0.2em] text-stone-400 uppercase">Performance</p>
                <h2 className="mt-2 text-2xl font-bold tracking-[-0.05em] text-stone-950">No workout data yet</h2>
                <p className="mt-2 text-sm leading-6 text-stone-600">Complete assigned workout sets and this profile will show session totals and PRs.</p>
            </section>
        );
    }

    const metricCards = [
        { label: 'Sets Completed', value: compactNumber(activeStats.setsCompleted), icon: ListChecks },
        { label: 'Total Reps', value: compactNumber(activeStats.totalReps), icon: Dumbbell },
        { label: 'Tonnage', value: compactNumber(activeStats.tonnage), icon: Medal, suffix: 'kg' },
        { label: 'Duration', value: formatDuration(activeStats.durationMinutes), icon: Clock3 },
    ];

    return (
        <section className="overflow-hidden rounded-[1.65rem] border border-stone-200 bg-white shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)] md:rounded-[2rem]">
            <div className="grid grid-cols-3 border-b border-stone-200 text-center text-sm font-semibold text-stone-400">
                {performanceTabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        onClick={() => setActiveTab(tab.key)}
                        className={`min-h-14 px-2 transition ${
                            activeTab === tab.key ? 'border-b-2 border-stone-950 text-stone-950' : 'hover:text-stone-700'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="space-y-5 p-4 md:p-6">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.2em] text-stone-400 uppercase">Profile performance</p>
                        <h2 className="mt-2 font-['Space_Grotesk'] text-2xl font-bold tracking-[-0.05em] text-stone-950">
                            {activeStats.sessionTitle ?? activeStats.label}
                        </h2>
                        <p className="mt-1 text-sm leading-6 text-stone-600">
                            {activeStats.sessionsLogged > 0
                                ? `${activeStats.programTitle ?? 'Training'}${activeStats.coachName ? ` with ${activeStats.coachName}` : ''}`
                                : 'No completed workout sets found for this range.'}
                        </p>
                    </div>
                    <div className="rounded-full bg-emerald-50 p-3 text-emerald-700">
                        <TimerReset className="size-5" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    {metricCards.map((card) => (
                        <div key={card.label} className="min-h-32 rounded-[1.35rem] bg-stone-50 p-4 shadow-[0_16px_35px_-32px_rgba(15,23,42,0.65)]">
                            <div className="flex items-start justify-between gap-3">
                                <p className="max-w-24 text-lg font-bold leading-tight tracking-[-0.04em] text-stone-800">{card.label}</p>
                                <card.icon className="size-6 text-stone-400" />
                            </div>
                            <p className="mt-6 text-4xl font-black tracking-[-0.08em] text-stone-900">
                                {card.value}
                                {card.suffix && <span className="ml-1 text-base font-semibold tracking-normal text-stone-500">{card.suffix}</span>}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="space-y-3">
                    <div className="flex items-center gap-3">
                        <Medal className="size-5 text-stone-500" />
                        <h3 className="text-lg font-bold tracking-[-0.04em] text-stone-900">New PRs</h3>
                    </div>

                    {prs.length > 0 ? (
                        <div className="space-y-3">
                            {prs.map((record) => (
                                <div
                                    key={`${record.exerciseName}-${record.load}`}
                                    className="flex items-center justify-between gap-3 rounded-[1.1rem] bg-emerald-900 px-4 py-4 text-white"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-lg font-semibold tracking-[-0.04em]">{record.exerciseName}</p>
                                        <p className="text-xs text-emerald-100">
                                            {record.reps ? `${compactNumber(record.reps)} reps` : 'Best recorded load'}
                                            {record.completedAt ? ` · ${record.completedAt}` : ''}
                                        </p>
                                    </div>
                                    <p className="shrink-0 text-4xl font-black tracking-[-0.08em]">{compactNumber(record.load)}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-[1.1rem] border border-dashed border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
                            PRs appear after completed sets include a recorded load.
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

export default function Profile({
    mustVerifyEmail,
    status,
    performance,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    performance?: ProfilePerformance;
}) {
    const { auth } = usePage<AuthenticatedSharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
        phone: auth.user.phone ?? '',
        primary_goal: auth.user.primary_goal ?? '',
        preferred_contact_method: auth.user.preferred_contact_method ?? 'email',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    const roleNames = auth.user.role_names ?? [];
    const primaryRole = auth.user.primary_role;
    const isOwnerOrAdmin = roleNames.includes('owner') || roleNames.includes('admin') || primaryRole === 'owner' || primaryRole === 'admin';
    const isAthleteAppUser = !isOwnerOrAdmin && (roleNames.includes('athlete') || primaryRole === 'athlete');
    const isCoachAppUser = !isOwnerOrAdmin && (roleNames.includes('coach') || primaryRole === 'coach');

    const profileInformation = (
        <div className="space-y-6">
            <HeadingSmall
                title="Profile information"
                description="Update the identity and contact details that drive onboarding and coach communication."
            />

            <form onSubmit={submit} className="space-y-6">
                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>

                    <Input
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        autoComplete="name"
                        placeholder="Full name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email address</Label>

                    <Input
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                        placeholder="Email address"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone number</Label>

                    <Input
                        id="phone"
                        type="tel"
                        className="mt-1 block w-full"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        autoComplete="tel"
                        placeholder="+966500000000"
                    />

                    <InputError className="mt-2" message={errors.phone} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="primary_goal">Primary goal</Label>

                    <Input
                        id="primary_goal"
                        className="mt-1 block w-full"
                        value={data.primary_goal}
                        onChange={(e) => setData('primary_goal', e.target.value)}
                        placeholder="Build strength while keeping recovery in line"
                    />

                    <InputError className="mt-2" message={errors.primary_goal} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="preferred_contact_method">Preferred contact method</Label>

                    <Select value={data.preferred_contact_method} onValueChange={(value) => setData('preferred_contact_method', value)}>
                        <SelectTrigger id="preferred_contact_method">
                            <SelectValue placeholder="Choose contact preference" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="email">Email</SelectItem>
                            <SelectItem value="phone">Phone</SelectItem>
                        </SelectContent>
                    </Select>

                    <InputError className="mt-2" message={errors.preferred_contact_method} />
                </div>

                {mustVerifyEmail && auth.user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-neutral-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <Button disabled={processing}>Save</Button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-neutral-600">Saved</p>
                    </Transition>
                </div>
            </form>
        </div>
    );

    const appProfileContent = (
        <div className="mx-auto max-w-3xl space-y-4 px-4 pt-5 pb-32 md:px-6 md:pt-6 md:pb-6">
            <section className="rounded-[1.65rem] border border-stone-200 bg-white p-4 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)] md:rounded-[2rem] md:p-6">
                <p className="text-xs font-semibold tracking-[0.2em] text-stone-400 uppercase">Account</p>
                <h1 className="mt-2 font-['Space_Grotesk'] text-2xl font-bold tracking-[-0.05em] text-stone-950 md:text-3xl">Profile settings</h1>
                <p className="mt-2 text-sm leading-6 text-stone-600">Manage your app profile, contact details, and coach communication preference.</p>
                <div className="mt-5 flex flex-wrap gap-2">
                    <Link href="/settings/profile" className="rounded-full bg-stone-950 px-4 py-2 text-sm font-semibold text-white">
                        Profile
                    </Link>
                    <Link
                        href="/settings/password"
                        className="rounded-full border border-stone-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700"
                    >
                        Password
                    </Link>
                </div>
            </section>

            <ProfilePerformancePanel performance={performance} />

            <section className="rounded-[1.65rem] border border-stone-200 bg-white p-4 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)] md:rounded-[2rem] md:p-6">
                {profileInformation}
            </section>

            <section className="rounded-[1.65rem] border border-red-100 bg-white p-4 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)] md:rounded-[2rem] md:p-6">
                <DeleteUser />
            </section>
        </div>
    );

    if (isAthleteAppUser) {
        return (
            <AthleteAppShell active="profile" breadcrumbs={breadcrumbs}>
                <Head title="Profile settings" />
                {appProfileContent}
            </AthleteAppShell>
        );
    }

    if (isCoachAppUser) {
        return (
            <CoachAppShell active="home">
                <Head title="Profile settings" />
                {appProfileContent}
            </CoachAppShell>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                {profileInformation}
                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
