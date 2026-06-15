import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/marketing-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, ArrowRight, CreditCard, Dumbbell, ShieldCheck, Watch } from 'lucide-react';

const operatingLayers = [
    {
        title: 'Coaching workflow',
        copy: 'Assign programs, review logs, and keep each athlete tied to a real coach instead of a spreadsheet ghost.',
        icon: Dumbbell,
    },
    {
        title: 'Progress visibility',
        copy: 'Track weight, recovery, soreness, food, hydration, and compliance without pretending the watch knows the whole story.',
        icon: Activity,
    },
    {
        title: 'Commercial control',
        copy: 'Keep memberships, renewals, payments, and device coverage in the same system so ops does not become chaos.',
        icon: CreditCard,
    },
];

const productSignals = [
    { label: 'Role-aware dashboard', value: 'Admin, coach, and athlete views' },
    { label: 'Manual + wearable metrics', value: 'WHOOP-ready with progress check-ins' },
    { label: 'Training detail', value: 'Programs, sets, reps, load, and logs' },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <MarketingLayout
            title="Throughline"
            description="Coach performance OS for subscriptions, athlete progress, wearable context, and training control."
        >
            <Head title="Throughline" />

            <section className="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div className="space-y-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-stone-900/10 bg-white/70 px-4 py-2 text-xs font-semibold tracking-[0.22em] text-stone-600 uppercase shadow-sm backdrop-blur">
                        <ShieldCheck className="h-4 w-4 text-amber-700" />
                        Coach the whole athlete
                    </div>

                    <div className="space-y-5">
                        <h1 className="max-w-4xl font-['Space_Grotesk'] text-5xl font-bold tracking-tight text-stone-950 sm:text-6xl">
                            Stop running coaching, recovery, payments, and progress from five disconnected tools.
                        </h1>
                        <p className="max-w-2xl text-lg leading-8 text-stone-700">
                            Throughline is the operating system for subscription-based coaching. Coaches manage rosters, training programs, athlete
                            check-ins, memberships, and device-backed metrics in one place instead of duct-taping apps together.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        {auth.user ? (
                            <Button asChild size="lg" className="rounded-full bg-stone-950 px-7 text-stone-50 hover:bg-stone-800">
                                <Link href={route('dashboard')}>
                                    Open dashboard
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </Button>
                        ) : (
                            <Button asChild size="lg" className="rounded-full bg-stone-950 px-7 text-stone-50 hover:bg-stone-800">
                                <Link href={route('register')}>
                                    Start building
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </Button>
                        )}
                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="rounded-full border-stone-950/20 bg-white/70 px-7 text-stone-900 hover:bg-white"
                        >
                            <Link href={route('contact.show')}>Talk to us</Link>
                        </Button>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        {productSignals.map((signal) => (
                            <div
                                key={signal.label}
                                className="rounded-3xl border border-stone-900/10 bg-white/70 p-5 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur"
                            >
                                <p className="text-xs font-semibold tracking-[0.2em] text-stone-500 uppercase">{signal.label}</p>
                                <p className="mt-3 text-base leading-6 font-semibold text-stone-900">{signal.value}</p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-900/10 bg-stone-950 text-stone-50 shadow-[0_28px_64px_rgba(55,41,19,0.22)]">
                    <div className="border-b border-white/10 px-6 py-5">
                        <p className="text-xs font-semibold tracking-[0.22em] text-stone-400 uppercase">Product loop</p>
                        <h2 className="mt-2 font-['Space_Grotesk'] text-2xl font-bold">
                            Recovery data only matters when it changes coaching decisions.
                        </h2>
                    </div>
                    <div className="space-y-4 px-6 py-6">
                        <div className="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <div className="flex items-center gap-3">
                                <Watch className="h-5 w-5 text-amber-300" />
                                <p className="font-semibold">Wearable layer</p>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-stone-300">
                                WHOOP and ingest-ready device connections feed readiness, sleep, HRV, strain, and training-load context into the
                                athlete record.
                            </p>
                        </div>
                        <div className="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <div className="flex items-center gap-3">
                                <Activity className="h-5 w-5 text-amber-300" />
                                <p className="font-semibold">Manual layer</p>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-stone-300">
                                Athletes log weight, soreness, food, hydration, stress, and sleep quality so coaches are not guessing off a single
                                recovery score.
                            </p>
                        </div>
                        <div className="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <div className="flex items-center gap-3">
                                <Dumbbell className="h-5 w-5 text-amber-300" />
                                <p className="font-semibold">Coaching action</p>
                            </div>
                            <p className="mt-3 text-sm leading-6 text-stone-300">
                                Programs, sets, reps, logs, membership status, and roster attention flags all stay in one command surface.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mt-16 grid gap-5 lg:grid-cols-3">
                {operatingLayers.map((layer) => {
                    const Icon = layer.icon;

                    return (
                        <article
                            key={layer.title}
                            className="rounded-[1.75rem] border border-stone-900/10 bg-white/70 p-6 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur"
                        >
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-900">
                                <Icon className="h-5 w-5" />
                            </div>
                            <h3 className="mt-5 font-['Space_Grotesk'] text-2xl font-bold tracking-tight text-stone-950">{layer.title}</h3>
                            <p className="mt-3 text-sm leading-7 text-stone-700">{layer.copy}</p>
                        </article>
                    );
                })}
            </section>

            <section className="mt-16 rounded-[2.25rem] border border-stone-900/10 bg-white/75 p-8 shadow-[0_22px_48px_rgba(61,47,27,0.08)] backdrop-blur lg:p-10">
                <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.24em] text-stone-500 uppercase">Need custom work or implementation help?</p>
                        <h2 className="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-stone-950">
                            Use the contact page and send us the real problem.
                        </h2>
                        <p className="mt-3 max-w-2xl text-sm leading-7 text-stone-700">
                            Tell us who you are, what kind of coaching operation you run, and what needs to integrate. We store the message in the
                            backend so it actually lands somewhere useful.
                        </p>
                    </div>
                    <Button asChild size="lg" className="rounded-full bg-stone-950 px-7 text-stone-50 hover:bg-stone-800">
                        <Link href={route('contact.show')}>
                            Open contact page
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </section>
        </MarketingLayout>
    );
}
