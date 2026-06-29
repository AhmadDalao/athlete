import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';
import { type CSSProperties, type ReactNode } from 'react';

interface AthleteHeroProps {
    eyebrow: string;
    title: string;
    description: string;
    badges?: string[];
    actions?: ReactNode;
    children: ReactNode;
}

export function AthleteHero({ eyebrow, title, description, badges = [], actions, children }: AthleteHeroProps) {
    return (
        <section className="relative min-w-0 overflow-hidden rounded-[2.2rem] border border-white/80 bg-[linear-gradient(135deg,rgba(255,247,237,0.96),rgba(255,255,255,0.98)_46%,rgba(236,253,245,0.94))] shadow-[0_36px_90px_-52px_rgba(15,23,42,0.42)]">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(251,191,36,0.24),transparent_30%),radial-gradient(circle_at_80%_20%,rgba(20,184,166,0.18),transparent_28%),linear-gradient(rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.08)_1px,transparent_1px)] bg-[length:auto,auto,38px_38px,38px_38px]" />
            <div className="relative grid min-w-0 gap-8 p-6 lg:grid-cols-[1.15fr_0.85fr] lg:p-8">
                <div className="min-w-0 space-y-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full border border-white/80 bg-white/88 px-3 py-1 text-[0.68rem] font-semibold tracking-[0.24em] text-stone-600 uppercase shadow-sm">
                            {eyebrow}
                        </span>
                        {badges.map((badge) => (
                            <Badge key={badge} variant="outline" className="border-white/80 bg-white/75 text-stone-700">
                                {badge}
                            </Badge>
                        ))}
                    </div>
                    <div className="max-w-3xl space-y-3">
                        <h1 className="max-w-2xl font-['Space_Grotesk'] text-4xl leading-none font-bold tracking-[-0.05em] text-stone-950 sm:text-5xl">
                            {title}
                        </h1>
                        <p className="max-w-2xl text-sm leading-7 text-stone-600 sm:text-base">{description}</p>
                    </div>
                    {actions && <div className="flex flex-wrap gap-3">{actions}</div>}
                </div>
                <div className="min-w-0">{children}</div>
            </div>
        </section>
    );
}

export function AthleteMetricCard({
    title,
    value,
    note,
    icon: Icon,
    tone = 'teal',
}: {
    title: string;
    value: string;
    note: string;
    icon: LucideIcon;
    tone?: 'teal' | 'amber' | 'stone';
}) {
    const toneClasses = {
        teal: 'from-teal-500/16 via-teal-500/10 to-white text-teal-900',
        amber: 'from-amber-400/20 via-orange-400/10 to-white text-orange-950',
        stone: 'from-stone-300/26 via-stone-200/12 to-white text-stone-900',
    } as const;

    return (
        <Card className={cn('min-w-0 overflow-hidden border-white/65 bg-linear-to-br shadow-sm', toneClasses[tone])}>
            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                    <CardDescription className="text-stone-500">{title}</CardDescription>
                    <CardTitle className="mt-3 text-3xl tracking-[-0.04em] break-words text-stone-950">{value}</CardTitle>
                </div>
                <div className="rounded-full border border-white/70 bg-white/80 p-2 text-stone-700 shadow-sm">
                    <Icon className="size-4" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-sm leading-6 text-stone-600">{note}</p>
            </CardContent>
        </Card>
    );
}

export function AthletePanel({
    title,
    description,
    children,
    className,
    contentClassName,
}: {
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
}) {
    return (
        <Card className={cn('min-w-0 overflow-hidden border-stone-200/75 bg-white/92 shadow-[0_24px_60px_-46px_rgba(15,23,42,0.65)]', className)}>
            <CardHeader className="p-4 pb-3 md:p-6">
                <CardTitle className="text-lg tracking-[-0.03em] text-stone-950 md:text-xl">{title}</CardTitle>
                {description && <CardDescription className="max-w-3xl leading-6 text-stone-600">{description}</CardDescription>}
            </CardHeader>
            <CardContent className={cn('min-w-0 p-4 pt-0 md:p-6 md:pt-0', contentClassName)}>{children}</CardContent>
        </Card>
    );
}

export function AthleteSectionHeading({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
    return (
        <div className="space-y-2">
            <p className="text-[0.68rem] font-semibold tracking-[0.24em] text-stone-500 uppercase">{eyebrow}</p>
            <div className="space-y-1">
                <h2 className="font-['Space_Grotesk'] text-2xl font-bold tracking-[-0.04em] text-stone-950">{title}</h2>
                <p className="max-w-3xl text-sm leading-7 text-stone-600">{description}</p>
            </div>
        </div>
    );
}

export function ReadinessDial({ score, label, note, detail }: { score: number | null; label: string; note: string; detail?: string }) {
    const normalized = score === null ? 0 : Math.max(0, Math.min(100, Math.round(score)));
    const accent = score === null ? 'rgba(148, 163, 184, 0.35)' : normalized >= 80 ? '#0f766e' : normalized >= 60 ? '#f59e0b' : '#dc2626';
    const dialStyle = {
        background: `conic-gradient(${accent} ${normalized}%, rgba(226, 232, 240, 0.75) ${normalized}% 100%)`,
    } satisfies CSSProperties;

    return (
        <div className="rounded-[1.75rem] border border-stone-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.94),rgba(248,250,252,0.92))] p-5">
            <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-4">
                    <div className="grid size-28 place-items-center rounded-full p-2 shadow-inner" style={dialStyle}>
                        <div className="grid size-full place-items-center rounded-full bg-white text-center text-stone-950 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.45),inset_0_1px_0_rgba(255,255,255,0.9)]">
                            <span className="text-3xl font-semibold tracking-tight">{score === null ? '--' : normalized}</span>
                            <span className="text-[0.63rem] tracking-[0.28em] text-stone-400 uppercase">Readiness</span>
                        </div>
                    </div>
                    <div className="space-y-1">
                        <p className="text-sm font-medium text-stone-500">{label}</p>
                        <p className="text-lg font-semibold tracking-tight text-stone-950">{note}</p>
                        {detail && <p className="text-sm leading-6 text-stone-600">{detail}</p>}
                    </div>
                </div>
            </div>
        </div>
    );
}

interface TrendPoint {
    label: string;
    value: number | null;
    note?: string | null;
}

export function TrendBars({
    title,
    description,
    points,
    formatter,
    tone = 'teal',
}: {
    title: string;
    description: string;
    points: TrendPoint[];
    formatter?: (value: number | null) => string;
    tone?: 'teal' | 'amber' | 'rose';
}) {
    const values = points.map((point) => point.value).filter((value): value is number => typeof value === 'number' && Number.isFinite(value));
    const min = values.length > 0 ? Math.min(...values) : 0;
    const max = values.length > 0 ? Math.max(...values) : 1;
    const range = Math.max(max - min, 1);
    const tones = {
        teal: 'from-teal-500 via-teal-400 to-cyan-300',
        amber: 'from-orange-500 via-amber-400 to-yellow-300',
        rose: 'from-rose-500 via-orange-400 to-amber-300',
    } as const;

    return (
        <div className="min-w-0 overflow-hidden rounded-[1.65rem] border border-stone-200/80 bg-white p-4 shadow-[0_24px_55px_-48px_rgba(15,23,42,0.55)] md:p-5">
            <div className="space-y-2">
                <p className="text-base font-semibold tracking-[-0.02em] text-stone-950">{title}</p>
                <p className="text-sm leading-6 text-stone-600">{description}</p>
            </div>
            <div className="mt-5 grid min-w-0 grid-cols-7 gap-1.5 sm:gap-2">
                {points.map((point) => {
                    const height = point.value === null ? 14 : Math.max(30, Math.round(((point.value - min) / range) * 58) + 34);

                    return (
                        <div key={`${point.label}-${point.note ?? 'base'}`} className="min-w-0 text-center">
                            <span className="hidden h-5 truncate text-[0.68rem] font-medium text-stone-500 sm:block">
                                {formatter ? formatter(point.value) : (point.value ?? 'N/A')}
                            </span>
                            <div className="mt-1 flex h-24 items-end justify-center sm:mt-2 sm:h-28">
                                <div
                                    className={cn('w-6 max-w-full rounded-full bg-linear-to-t shadow-sm transition-all sm:w-8 md:w-10', tones[tone])}
                                    style={{ height: `${height}%` }}
                                />
                            </div>
                            <div className="mt-2 min-h-11 text-center sm:mt-3 sm:min-h-12">
                                <p className="text-[0.58rem] font-semibold tracking-[0.14em] text-stone-500 uppercase sm:text-[0.62rem]">
                                    {point.label}
                                </p>
                                {point.note && <p className="mt-1 truncate text-[0.62rem] text-stone-500 sm:text-[0.68rem]">{point.note}</p>}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
