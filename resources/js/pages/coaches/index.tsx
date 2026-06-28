import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useAutoFilter } from '@/hooks/use-auto-filter';
import MarketingLayout from '@/layouts/marketing-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, Search, ShieldCheck, Users, Watch } from 'lucide-react';
import type { FormEvent } from 'react';

interface CoachRow {
    id: number;
    name: string;
    email: string;
    headline: string;
    rosterCount: number;
    activePrograms: number;
    connectedAthletesCount: number;
    whoopRosterCount: number;
    recentCheckInCount: number;
    signals: string[];
    contactUrl: string;
}

interface CoachesPageProps {
    filters: {
        q: string | null;
    };
    summary: {
        totalCoaches: number;
        activeRosterSeats: number;
        activePrograms: number;
        wearableAwareCoaches: number;
    };
    coaches: CoachRow[];
}

export default function CoachesIndex({ filters, summary, coaches }: CoachesPageProps) {
    const { data, setData, get, processing } = useForm({
        q: filters.q ?? '',
    });
    const filterPayload = {
        q: data.q.trim() || undefined,
    };

    useAutoFilter({ url: route('coaches.index'), payload: filterPayload, only: ['filters', 'summary', 'coaches'], debounceMs: 220 });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        get(route('coaches.index'), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <MarketingLayout
            title="Coach Discovery"
            description="Browse coaches already operating inside Throughline and start the first real coach discovery slice."
        >
            <Head title="Coach Discovery" />

            <section className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                <div className="space-y-6">
                    <div className="inline-flex items-center gap-2 rounded-full border border-stone-900/10 bg-white/70 px-4 py-2 text-xs font-semibold tracking-[0.22em] text-stone-600 uppercase shadow-sm backdrop-blur">
                        <ShieldCheck className="h-4 w-4 text-amber-700" />
                        Phase 3 starter
                    </div>

                    <div className="space-y-4">
                        <h1 className="max-w-4xl font-['Space_Grotesk'] text-5xl font-bold tracking-tight text-stone-950 sm:text-6xl">
                            Find coaches already running recovery-aware operations instead of generic fitness noise.
                        </h1>
                        <p className="max-w-2xl text-lg leading-8 text-stone-700">
                            This is the first real coach discovery layer. It shows which coaches are already managing rosters, active programs, and
                            live device-backed athletes inside Throughline.
                        </p>
                    </div>

                    <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-stone-500" />
                            <Input
                                value={data.q}
                                onChange={(event) => setData('q', event.target.value)}
                                placeholder="Search by name, email, or coaching focus"
                                className="h-12 rounded-full border-stone-300/80 bg-white/80 pl-11"
                            />
                        </div>
                        <Button type="submit" disabled={processing} className="h-12 rounded-full bg-stone-950 px-7 text-stone-50 hover:bg-stone-800">
                            {processing ? 'Filtering...' : 'Search'}
                        </Button>
                    </form>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div className="rounded-[1.75rem] border border-stone-900/10 bg-white/75 p-6 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur">
                        <p className="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Discovery coverage</p>
                        <p className="mt-3 text-4xl font-semibold tracking-tight text-stone-950">{summary.totalCoaches}</p>
                        <p className="mt-2 text-sm leading-6 text-stone-700">Coach profiles currently visible in this first marketplace slice.</p>
                    </div>
                    <div className="rounded-[1.75rem] border border-stone-900/10 bg-white/75 p-6 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur">
                        <p className="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Live operations</p>
                        <p className="mt-3 text-4xl font-semibold tracking-tight text-stone-950">{summary.activePrograms}</p>
                        <p className="mt-2 text-sm leading-6 text-stone-700">
                            Active programs are already running across {summary.activeRosterSeats} roster seat(s).
                        </p>
                    </div>
                    <div className="rounded-[1.75rem] border border-stone-900/10 bg-white/75 p-6 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur">
                        <p className="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">Wearable-aware coaches</p>
                        <p className="mt-3 text-4xl font-semibold tracking-tight text-stone-950">{summary.wearableAwareCoaches}</p>
                        <p className="mt-2 text-sm leading-6 text-stone-700">
                            These coaches already have athletes feeding live device signal into the workflow.
                        </p>
                    </div>
                </div>
            </section>

            <section className="mt-14 space-y-5">
                {coaches.length === 0 ? (
                    <Card className="rounded-[2rem] border-stone-900/10 bg-white/80 shadow-[0_18px_40px_rgba(61,47,27,0.08)]">
                        <CardHeader>
                            <CardTitle className="font-['Space_Grotesk'] text-2xl font-bold tracking-tight text-stone-950">
                                No coach matched that search.
                            </CardTitle>
                            <CardDescription className="text-base leading-7 text-stone-700">
                                Try a broader term. If nothing shows up, that means the discovery layer is honest, not empty calories.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    coaches.map((coach) => (
                        <Card
                            key={coach.id}
                            className="rounded-[2rem] border-stone-900/10 bg-white/82 shadow-[0_20px_44px_rgba(61,47,27,0.08)] backdrop-blur"
                        >
                            <CardHeader className="gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <CardTitle className="font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-stone-950">
                                        {coach.name}
                                    </CardTitle>
                                    <CardDescription className="mt-2 max-w-2xl text-base leading-7 text-stone-700">{coach.headline}</CardDescription>
                                    <p className="mt-3 text-sm text-stone-500">{coach.email}</p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" className="border-stone-300/80 bg-white/70 text-stone-700">
                                        {coach.rosterCount} athletes
                                    </Badge>
                                    <Badge variant="outline" className="border-stone-300/80 bg-white/70 text-stone-700">
                                        {coach.activePrograms} active programs
                                    </Badge>
                                    {coach.whoopRosterCount > 0 && (
                                        <Badge variant="outline" className="border-stone-300/80 bg-white/70 text-stone-700">
                                            {coach.whoopRosterCount} WHOOP-linked
                                        </Badge>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="rounded-[1.35rem] border border-stone-200/80 bg-stone-50/80 p-4">
                                        <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Roster</p>
                                        <p className="mt-2 text-2xl font-semibold tracking-tight text-stone-950">{coach.rosterCount}</p>
                                        <p className="mt-2 text-sm leading-6 text-stone-600">
                                            Active athlete relationships currently under this coach.
                                        </p>
                                    </div>
                                    <div className="rounded-[1.35rem] border border-stone-200/80 bg-stone-50/80 p-4">
                                        <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Live device data</p>
                                        <p className="mt-2 text-2xl font-semibold tracking-tight text-stone-950">{coach.connectedAthletesCount}</p>
                                        <p className="mt-2 text-sm leading-6 text-stone-600">
                                            Athlete(s) already feeding recovery or activity signal into the workflow.
                                        </p>
                                    </div>
                                    <div className="rounded-[1.35rem] border border-stone-200/80 bg-stone-50/80 p-4">
                                        <p className="text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">Recent check-ins</p>
                                        <p className="mt-2 text-2xl font-semibold tracking-tight text-stone-950">{coach.recentCheckInCount}</p>
                                        <p className="mt-2 text-sm leading-6 text-stone-600">
                                            Athletes who logged a manual check-in in the last seven days.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    {coach.signals.map((signal) => (
                                        <Badge key={signal} variant="secondary" className="rounded-full px-3 py-1">
                                            {signal}
                                        </Badge>
                                    ))}
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex items-center gap-2 text-sm text-stone-600">
                                        <Users className="h-4 w-4 text-amber-800" />
                                        Marketplace is not fully here yet, but discovery is finally real.
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <Button asChild variant="outline" className="rounded-full border-stone-300/80 bg-white/75">
                                            <Link href={route('contact.show')}>General inquiry</Link>
                                        </Button>
                                        <Button asChild className="rounded-full bg-stone-950 text-stone-50 hover:bg-stone-800">
                                            <Link href={coach.contactUrl}>
                                                Contact this coach
                                                <ArrowRight className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))
                )}
            </section>

            <section className="mt-16 rounded-[2.2rem] border border-stone-900/10 bg-stone-950 p-8 text-stone-50 shadow-[0_28px_64px_rgba(55,41,19,0.22)] lg:p-10">
                <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.22em] text-stone-400 uppercase">Next marketplace steps</p>
                        <h2 className="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight">
                            Discovery is live. Reviews, payouts, and full marketplace mechanics come next.
                        </h2>
                        <p className="mt-3 max-w-2xl text-sm leading-7 text-stone-300">
                            This first slice proves the public coach layer and keeps it tied to real operating data instead of fake brochure copy.
                        </p>
                    </div>
                    <div className="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm">
                        <Watch className="h-4 w-4 text-amber-300" />
                        Coaches with live device-backed rosters are easier to trust.
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
