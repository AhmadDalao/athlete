import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { PropsWithChildren } from 'react';

interface MarketingLayoutProps extends PropsWithChildren {
    title: string;
    description?: string;
}

export default function MarketingLayout({ children, title, description }: MarketingLayoutProps) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title={title}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
                {description ? <meta name="description" content={description} /> : null}
            </Head>

            <div className="min-h-screen bg-[radial-gradient(circle_at_top,_#fbf6ea_0,_#f6efde_34%,_#efe6d2_64%,_#ece1ce_100%)] text-stone-950">
                <div className="absolute inset-0 bg-[linear-gradient(rgba(120,91,35,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(120,91,35,0.08)_1px,transparent_1px)] bg-[size:48px_48px] opacity-35" />
                <div className="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 lg:px-10">
                    <header className="rounded-[1.8rem] border border-white/70 bg-white/72 px-5 py-4 shadow-[0_20px_48px_-36px_rgba(15,23,42,0.28)] backdrop-blur">
                        <div className="flex items-center justify-between gap-6">
                            <Link href={route('home')} className="flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-stone-950 text-stone-50 shadow-[0_16px_36px_rgba(24,24,22,0.18)]">
                                    <AppLogoIcon className="h-5 w-5 fill-current" />
                                </div>
                                <div>
                                    <p className="font-['Space_Grotesk'] text-lg font-bold tracking-tight">Throughline</p>
                                    <p className="text-xs tracking-[0.22em] text-stone-600 uppercase">Coach performance OS</p>
                                </div>
                            </Link>

                            <nav className="hidden items-center gap-7 text-sm font-medium text-stone-700 md:flex">
                                <Link href={route('home')} className="transition hover:text-stone-950">
                                    Product
                                </Link>
                                <Link href={route('coaches.index')} className="transition hover:text-stone-950">
                                    Coaches
                                </Link>
                                <Link href={route('contact.show')} className="transition hover:text-stone-950">
                                    Contact
                                </Link>
                            </nav>

                            <div className="flex items-center gap-3">
                                {auth.user ? (
                                    <Button asChild className="rounded-full bg-stone-950 px-5 text-stone-50 hover:bg-stone-800">
                                        <Link href={route('dashboard')}>
                                            Dashboard
                                            <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            asChild
                                            variant="ghost"
                                            className="hidden rounded-full text-stone-700 hover:bg-stone-950/5 hover:text-stone-950 md:inline-flex"
                                        >
                                            <Link href={route('login')}>Log in</Link>
                                        </Button>
                                        <Button asChild className="rounded-full bg-stone-950 px-5 text-stone-50 hover:bg-stone-800">
                                            <Link href={route('register')}>Start free</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </header>

                    <div className="mt-4 flex gap-2 md:hidden">
                        <Button asChild variant="outline" className="rounded-full border-stone-900/10 bg-white/70 text-stone-700 hover:bg-white">
                            <Link href={route('coaches.index')}>Coaches</Link>
                        </Button>
                        <Button asChild variant="outline" className="rounded-full border-stone-900/10 bg-white/70 text-stone-700 hover:bg-white">
                            <Link href={route('contact.show')}>Contact</Link>
                        </Button>
                    </div>

                    <main className="flex-1 py-10 lg:py-14">{children}</main>

                    <footer className="mt-10 flex flex-col gap-4 border-t border-stone-900/10 pt-5 text-sm text-stone-600 md:flex-row md:items-center md:justify-between">
                        <p>Throughline gives coaches one place to run programming, progress, memberships, and device-backed athlete context.</p>
                        <div className="flex items-center gap-5">
                            <Link href={route('home')} className="transition hover:text-stone-950">
                                Home
                            </Link>
                            <Link href={route('coaches.index')} className="transition hover:text-stone-950">
                                Coaches
                            </Link>
                            <Link href={route('contact.show')} className="transition hover:text-stone-950">
                                Contact
                            </Link>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}
