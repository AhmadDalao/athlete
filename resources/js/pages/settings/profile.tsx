import { type AuthenticatedSharedData, type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
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
