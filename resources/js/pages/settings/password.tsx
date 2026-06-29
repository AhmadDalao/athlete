import { AthleteAppShell } from '@/components/athlete-app-shell';
import { CoachAppShell } from '@/components/coach-app-shell';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type AuthenticatedSharedData, type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: '/settings/password',
    },
];

export default function Password() {
    const { auth } = usePage<AuthenticatedSharedData>().props;
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    const roleNames = auth.user.role_names ?? [];
    const primaryRole = auth.user.primary_role;
    const isOwnerOrAdmin = roleNames.includes('owner') || roleNames.includes('admin') || primaryRole === 'owner' || primaryRole === 'admin';
    const isAthleteAppUser = !isOwnerOrAdmin && (roleNames.includes('athlete') || primaryRole === 'athlete');
    const isCoachAppUser = !isOwnerOrAdmin && (roleNames.includes('coach') || primaryRole === 'coach');

    const passwordForm = (
        <div className="space-y-6">
            <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

            <form onSubmit={updatePassword} className="space-y-6">
                <div className="grid gap-2">
                    <Label htmlFor="current_password">Current password</Label>

                    <Input
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        placeholder="Current password"
                    />

                    <InputError message={errors.current_password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">New password</Label>

                    <Input
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        placeholder="New password"
                    />

                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Confirm password</Label>

                    <Input
                        id="password_confirmation"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        placeholder="Confirm password"
                    />

                    <InputError message={errors.password_confirmation} />
                </div>

                <div className="flex items-center gap-4">
                    <Button disabled={processing}>Save password</Button>

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

    const appPasswordContent = (
        <div className="mx-auto max-w-3xl space-y-4 px-4 pt-5 pb-32 md:px-6 md:pt-6 md:pb-6">
            <section className="rounded-[1.65rem] border border-stone-200 bg-white p-4 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)] md:rounded-[2rem] md:p-6">
                <p className="text-xs font-semibold tracking-[0.2em] text-stone-400 uppercase">Account</p>
                <h1 className="mt-2 font-['Space_Grotesk'] text-2xl font-bold tracking-[-0.05em] text-stone-950 md:text-3xl">Password settings</h1>
                <p className="mt-2 text-sm leading-6 text-stone-600">
                    Keep account access inside the app experience without dropping into the backend workspace.
                </p>
                <div className="mt-5 flex flex-wrap gap-2">
                    <Link
                        href="/settings/profile"
                        className="rounded-full border border-stone-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700"
                    >
                        Profile
                    </Link>
                    <Link href="/settings/password" className="rounded-full bg-stone-950 px-4 py-2 text-sm font-semibold text-white">
                        Password
                    </Link>
                </div>
            </section>

            <section className="rounded-[1.65rem] border border-stone-200 bg-white p-4 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.45)] md:rounded-[2rem] md:p-6">
                {passwordForm}
            </section>
        </div>
    );

    if (isAthleteAppUser) {
        return (
            <AthleteAppShell active="profile" breadcrumbs={breadcrumbs}>
                <Head title="Password settings" />
                {appPasswordContent}
            </AthleteAppShell>
        );
    }

    if (isCoachAppUser) {
        return (
            <CoachAppShell active="home">
                <Head title="Password settings" />
                {appPasswordContent}
            </CoachAppShell>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Password settings" />

            <SettingsLayout>{passwordForm}</SettingsLayout>
        </AppLayout>
    );
}
