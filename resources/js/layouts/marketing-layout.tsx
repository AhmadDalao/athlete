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

            <div className="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(168,255,47,0.16),transparent_32%),linear-gradient(135deg,#070b0d_0%,#10171a_42%,#050809_100%)] text-stone-50">
                <div className="absolute inset-0 bg-[linear-gradient(rgba(168,255,47,0.055)_1px,transparent_1px),linear-gradient(90deg,rgba(168,255,47,0.045)_1px,transparent_1px)] bg-[size:48px_48px] opacity-45" />
                <div className="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 lg:px-10">
                    <header className="rounded-[1.8rem] border border-white/10 bg-white/[0.05] px-5 py-4 shadow-[0_24px_70px_-46px_rgba(0,0,0,0.9)] backdrop-blur">
                        <div className="flex items-center justify-between gap-6">
                            <Link href={route('home')} className="flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-lime-300 text-[#07100c] shadow-[0_18px_42px_-28px_rgba(168,255,47,0.8)]">
                                    <AppLogoIcon className="h-5 w-5 fill-current" />
                                </div>
                                <div>
                                    <p className="font-['Space_Grotesk'] text-lg font-bold tracking-tight">Throughline</p>
                                    <p className="text-xs tracking-[0.22em] text-lime-200/70 uppercase">Coach performance OS</p>
                                </div>
                            </Link>

                            <nav className="hidden items-center gap-7 text-sm font-medium text-stone-300 md:flex">
                                <Link href={route('home')} className="transition hover:text-lime-200">
                                    Product
                                </Link>
                                <Link href={route('coaches.index')} className="transition hover:text-lime-200">
                                    Coaches
                                </Link>
                                <Link href={route('contact.show')} className="transition hover:text-lime-200">
                                    Contact
                                </Link>
                            </nav>

                            <div className="flex items-center gap-3">
                                {auth.user ? (
                                    <Button asChild className="rounded-full bg-lime-300 px-5 text-[#07100c] hover:bg-lime-200">
                                        <Link href={auth.user.landing_path ?? '/app'}>
                                            Open app
                                            <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="hidden rounded-full border-white/10 bg-white/[0.05] text-stone-100 hover:bg-white/10 hover:text-white md:inline-flex"
                                        >
                                            <Link href={route('login')}>Log in</Link>
                                        </Button>
                                        <Button asChild className="rounded-full bg-lime-300 px-5 text-[#07100c] hover:bg-lime-200">
                                            <Link href={route('register')}>Start free</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </header>

                    <div className="mt-4 flex flex-wrap gap-2 md:hidden">
                        {!auth.user && (
                            <Button asChild className="rounded-full bg-lime-300 px-5 text-[#07100c] hover:bg-lime-200">
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                        )}
                        <Button asChild variant="outline" className="rounded-full border-white/10 bg-white/[0.05] text-stone-100 hover:bg-white/10 hover:text-white">
                            <Link href={route('coaches.index')}>Coaches</Link>
                        </Button>
                        <Button asChild variant="outline" className="rounded-full border-white/10 bg-white/[0.05] text-stone-100 hover:bg-white/10 hover:text-white">
                            <Link href={route('contact.show')}>Contact</Link>
                        </Button>
                    </div>

                    <main className="flex-1 py-10 lg:py-14">{children}</main>

                    <footer className="mt-10 flex flex-col gap-4 border-t border-white/10 pt-5 text-sm text-stone-400 md:flex-row md:items-center md:justify-between">
                        <p>Throughline gives coaches one place to run programming, progress, memberships, and device-backed athlete context.</p>
                        <div className="flex items-center gap-5">
                            <Link href={route('home')} className="transition hover:text-lime-200">
                                Home
                            </Link>
                            <Link href={route('coaches.index')} className="transition hover:text-lime-200">
                                Coaches
                            </Link>
                            <Link href={route('contact.show')} className="transition hover:text-lime-200">
                                Contact
                            </Link>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}
