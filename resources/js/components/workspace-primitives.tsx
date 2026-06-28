import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

export function WorkspaceHero({
    eyebrow,
    title,
    description,
    badges = [],
    actions,
    aside,
}: {
    eyebrow: string;
    title: string;
    description: string;
    badges?: string[];
    actions?: ReactNode;
    aside?: ReactNode;
}) {
    return (
        <section className="min-w-0 rounded-[2rem] border border-stone-200/90 bg-white shadow-[0_18px_38px_-34px_rgba(15,23,42,0.16)]">
            <div className="grid min-w-0 gap-6 p-6 lg:grid-cols-[1.12fr_0.88fr] lg:p-8">
                <div className="min-w-0 space-y-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-[0.68rem] font-semibold tracking-[0.22em] text-stone-600 uppercase">
                            {eyebrow}
                        </span>
                        {badges.map((badge) => (
                            <Badge key={badge} variant="outline" className="border-stone-200 bg-white text-stone-700">
                                {badge}
                            </Badge>
                        ))}
                    </div>
                    <div className="space-y-4">
                        <h1 className="max-w-3xl font-['Space_Grotesk'] text-3xl leading-none font-bold tracking-[-0.04em] text-stone-950 sm:text-[2.7rem]">
                            {title}
                        </h1>
                        <p className="max-w-2xl text-sm leading-7 text-stone-600">{description}</p>
                    </div>
                    {actions && <div className="flex min-w-0 flex-wrap gap-3">{actions}</div>}
                </div>
                {aside && <div className="min-w-0 lg:pl-4">{aside}</div>}
            </div>
        </section>
    );
}

export function WorkspaceMetricCard({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: LucideIcon }) {
    return (
        <Card className="min-w-0 border-stone-200/90 bg-white shadow-[0_14px_30px_-28px_rgba(15,23,42,0.14)]">
            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                    <CardDescription className="text-stone-500">{title}</CardDescription>
                    <CardTitle className="mt-3 text-3xl tracking-[-0.04em] text-stone-950">{value}</CardTitle>
                </div>
                <div className="rounded-2xl border border-stone-200 bg-stone-50 p-2 text-stone-700">
                    <Icon className="size-4" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-sm leading-6 text-stone-600">{note}</p>
            </CardContent>
        </Card>
    );
}

export function WorkspaceActionCard({ title, href, note, icon: Icon }: { title: string; href: string; note: string; icon: LucideIcon }) {
    return (
        <Link href={href} className="block min-w-0">
            <Card className="h-full min-w-0 border-stone-200/90 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-stone-300 hover:bg-white">
                <CardHeader className="flex flex-row items-start justify-between space-y-0">
                    <div>
                        <CardTitle className="text-lg tracking-tight text-stone-950">{title}</CardTitle>
                        <CardDescription className="mt-2 leading-6 text-stone-600">{note}</CardDescription>
                    </div>
                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-2 text-stone-700">
                        <Icon className="size-4" />
                    </div>
                </CardHeader>
                <CardContent className="flex items-center text-sm font-medium text-stone-800">
                    Open view
                    <ArrowRight className="ml-2 size-4" />
                </CardContent>
            </Card>
        </Link>
    );
}

export function WorkspacePanel({
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
        <Card className={cn('min-w-0 border-stone-200/90 bg-white shadow-[0_14px_30px_-28px_rgba(15,23,42,0.14)]', className)}>
            <CardHeader>
                <CardTitle className="text-xl tracking-[-0.03em] text-stone-950">{title}</CardTitle>
                {description && <CardDescription className="max-w-3xl leading-6 text-stone-600">{description}</CardDescription>}
            </CardHeader>
            <CardContent className={contentClassName}>{children}</CardContent>
        </Card>
    );
}

export function WorkspaceSectionHeading({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
    return (
        <div className="space-y-2">
            <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-400 uppercase">{eyebrow}</p>
            <div className="space-y-1">
                <h2 className="font-['Space_Grotesk'] text-2xl font-bold tracking-[-0.04em] text-stone-950">{title}</h2>
                <p className="max-w-3xl text-sm leading-7 text-stone-600">{description}</p>
            </div>
        </div>
    );
}

export function WorkspaceTable({
    children,
    minWidth = 'min-w-[980px]',
}: {
    children: ReactNode;
    minWidth?: string;
}) {
    return (
        <div className="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
            <table className={cn('w-full text-left text-sm', minWidth)}>{children}</table>
        </div>
    );
}

export function WorkspaceTableHeader({ labels }: { labels: string[] }) {
    return (
        <thead className="bg-stone-50 text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
            <tr>
                {labels.map((label) => (
                    <th key={label} className="px-4 py-3">
                        {label}
                    </th>
                ))}
            </tr>
        </thead>
    );
}

export function WorkspaceTableEmpty({ message, colSpan }: { message: string; colSpan: number }) {
    return (
        <tbody>
            <tr>
                <td colSpan={colSpan} className="px-4 py-8 text-center text-sm text-stone-500">
                    {message}
                </td>
            </tr>
        </tbody>
    );
}
