import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';
import { Activity, CreditCard, Watch } from 'lucide-react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="relative min-h-svh overflow-hidden px-6 py-8 md:px-10">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(251,191,36,0.18),transparent_26%),radial-gradient(circle_at_82%_6%,rgba(20,184,166,0.12),transparent_20%),linear-gradient(180deg,rgba(255,251,235,0.92),rgba(244,239,228,0.98))]" />
            <div className="absolute inset-0 bg-[linear-gradient(rgba(120,113,108,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(120,113,108,0.06)_1px,transparent_1px)] bg-[size:42px_42px]" />
            <div className="relative mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center gap-8 lg:grid-cols-[0.95fr_1.05fr]">
                <div className="hidden rounded-[2.15rem] border border-white/70 bg-[linear-gradient(135deg,rgba(255,247,237,0.94),rgba(255,255,255,0.96)_42%,rgba(236,253,245,0.94))] p-8 text-stone-950 shadow-[0_36px_100px_-56px_rgba(15,23,42,0.48)] lg:block">
                    <Link href={route('home')} className="inline-flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,rgba(15,118,110,0.92),rgba(245,158,11,0.92))] text-white shadow-[0_18px_34px_-18px_rgba(13,148,136,0.55)]">
                            <AppLogoIcon className="size-6 fill-current" />
                        </div>
                        <div>
                            <p className="font-['Space_Grotesk'] text-xl font-bold tracking-tight">Throughline</p>
                            <p className="text-xs tracking-[0.22em] text-stone-500 uppercase">Coach performance OS</p>
                        </div>
                    </Link>

                    <div className="mt-12 space-y-5">
                        <p className="text-xs font-semibold tracking-[0.24em] text-teal-800 uppercase">Built for real coaching operations</p>
                        <h1 className="font-['Space_Grotesk'] text-5xl font-bold tracking-tight text-stone-950">
                            Roster, recovery, memberships, and training should feel like one product.
                        </h1>
                        <p className="max-w-xl text-sm leading-7 text-stone-600">
                            Throughline keeps athlete context, coach action, and subscription control in one surface so the daily workflow stops
                            breaking across random tools.
                        </p>
                    </div>

                    <div className="mt-10 grid gap-4">
                        <div className="rounded-[1.5rem] border border-white/80 bg-white/76 p-4">
                            <div className="flex items-center gap-3">
                                <Watch className="size-5 text-amber-700" />
                                <p className="font-semibold">Wearable context</p>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-stone-600">
                                WHOOP-ready recovery, sleep, strain, and sync health without leaving the athlete record.
                            </p>
                        </div>
                        <div className="rounded-[1.5rem] border border-white/80 bg-white/76 p-4">
                            <div className="flex items-center gap-3">
                                <Activity className="size-5 text-amber-700" />
                                <p className="font-semibold">Training and progress</p>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-stone-600">
                                Programs, workouts, weight, food, hydration, and compliance stay tied to the right athlete.
                            </p>
                        </div>
                        <div className="rounded-[1.5rem] border border-white/80 bg-white/76 p-4">
                            <div className="flex items-center gap-3">
                                <CreditCard className="size-5 text-amber-700" />
                                <p className="font-semibold">Membership control</p>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-stone-600">
                                Billing, renewals, and access rules stop living in someone’s memory and start living in the app.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="w-full max-w-2xl justify-self-center lg:justify-self-end">
                    <div className="rounded-[2.15rem] border border-white/75 bg-white/88 p-6 shadow-[0_30px_90px_-52px_rgba(15,23,42,0.52)] backdrop-blur md:p-8">
                        <div className="mb-8 flex flex-col gap-5">
                            <Link href={route('home')} className="flex items-center gap-3 font-medium lg:hidden">
                                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,rgba(15,118,110,0.92),rgba(245,158,11,0.92))] text-white">
                                    <AppLogoIcon className="size-6 fill-current" />
                                </div>
                                <div>
                                    <p className="font-['Space_Grotesk'] text-lg font-bold tracking-tight">Throughline</p>
                                    <p className="text-xs tracking-[0.22em] text-stone-500 uppercase">Coach performance OS</p>
                                </div>
                            </Link>

                            <div className="space-y-2">
                                <p className="text-xs font-semibold tracking-[0.24em] text-stone-500 uppercase">Access</p>
                                <h2 className="font-['Space_Grotesk'] text-3xl font-bold tracking-[-0.04em] text-stone-950">{title}</h2>
                                <p className="text-sm leading-7 text-stone-600">{description}</p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
