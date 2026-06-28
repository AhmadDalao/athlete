import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

export const workspaceTablePageSizeOptions = ['10', '25', '50', '100', 'all'] as const;

export function normalizeWorkspaceTablePageSize(value: string | number | null | undefined) {
    const normalized = String(value ?? '10').toLowerCase();

    return workspaceTablePageSizeOptions.includes(normalized as (typeof workspaceTablePageSizeOptions)[number]) ? normalized : '10';
}

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
        <section className="min-w-0 border-b border-stone-200 bg-white pb-7">
            <div className="flex min-w-0 flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div className="min-w-0 space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-[0.72rem] font-black tracking-[0.08em] text-amber-700">
                            {eyebrow}
                        </span>
                        {badges.map((badge) => (
                            <Badge key={badge} variant="outline" className="rounded-full border-stone-200 bg-white text-stone-700">
                                {badge}
                            </Badge>
                        ))}
                    </div>
                    <div className="space-y-2">
                        <h1 className="max-w-3xl font-['Space_Grotesk'] text-4xl leading-none font-black tracking-[-0.06em] text-stone-950 sm:text-[2.65rem]">
                            {title}
                        </h1>
                        <p className="max-w-2xl text-base leading-7 text-stone-500">{description}</p>
                    </div>
                </div>
                {actions && <div className="flex min-w-0 shrink-0 flex-wrap gap-3">{actions}</div>}
            </div>
            {aside && <div className="mt-5 min-w-0 border-t border-stone-100 pt-5">{aside}</div>}
        </section>
    );
}

export function WorkspaceMetricCard({ title, value, note, icon: Icon }: { title: string; value: string; note: string; icon: LucideIcon }) {
    return (
        <div className="min-w-0 border-t border-stone-100 bg-white py-4">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-xs font-black tracking-[0.14em] text-stone-500 uppercase">{title}</p>
                    <p className="mt-2 font-['Space_Grotesk'] text-3xl font-black tracking-[-0.06em] text-stone-950">{value}</p>
                </div>
                <div className="grid size-8 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-700">
                    <Icon className="size-4" />
                </div>
            </div>
            <p className="mt-3 text-sm leading-6 text-stone-500">{note}</p>
        </div>
    );
}

export function WorkspaceActionCard({ title, href, note, icon: Icon }: { title: string; href: string; note: string; icon: LucideIcon }) {
    return (
        <Link href={href} className="block min-w-0">
            <div className="flex h-full min-w-0 items-start justify-between gap-4 border-t border-stone-100 bg-white py-4 transition-colors hover:bg-stone-50">
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <Icon className="size-4 text-amber-700" />
                        <p className="font-['Space_Grotesk'] text-lg font-black tracking-[-0.04em] text-stone-950">{title}</p>
                    </div>
                    <p className="mt-2 text-sm leading-6 text-stone-500">{note}</p>
                </div>
                <span className="inline-flex shrink-0 items-center text-sm font-black text-stone-900">
                    Open view
                    <ArrowRight className="ml-1.5 size-4" />
                </span>
            </div>
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
        <section className={cn('min-w-0 rounded-[1.35rem] border border-stone-200 bg-white', className)}>
            <div className="flex flex-col gap-3 border-b border-stone-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div className="min-w-0">
                    <h2 className="font-['Space_Grotesk'] text-2xl font-black tracking-[-0.05em] text-stone-950">{title}</h2>
                    {description && <p className="mt-1 max-w-4xl text-sm leading-6 text-stone-500">{description}</p>}
                </div>
            </div>
            <div className={cn('p-6', contentClassName)}>{children}</div>
        </section>
    );
}

export function WorkspaceSectionHeading({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
    return (
        <div className="space-y-2">
            <p className="text-[0.7rem] font-black tracking-[0.14em] text-stone-400 uppercase">{eyebrow}</p>
            <div className="space-y-1">
                <h2 className="font-['Space_Grotesk'] text-2xl font-black tracking-[-0.05em] text-stone-950">{title}</h2>
                <p className="max-w-4xl text-sm leading-7 text-stone-500">{description}</p>
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
        <div className="overflow-x-auto rounded-[1.05rem] border border-stone-200 bg-white">
            <table className={cn('w-full border-collapse text-left text-sm', minWidth)}>{children}</table>
        </div>
    );
}

export function WorkspaceTablePageSize({
    value,
    onChange,
    className,
}: {
    value: string | number | null | undefined;
    onChange: (value: string) => void;
    className?: string;
}) {
    return (
        <div className={cn('flex flex-wrap items-center gap-3 text-sm font-black text-stone-700', className)}>
            <span>Show</span>
            <Select value={normalizeWorkspaceTablePageSize(value)} onValueChange={onChange}>
                <SelectTrigger className="h-11 w-24 rounded-xl border-stone-200 bg-white font-black text-stone-950">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {workspaceTablePageSizeOptions.map((option) => (
                        <SelectItem key={option} value={option}>
                            {option === 'all' ? 'All' : option}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <span>entries</span>
        </div>
    );
}

export function WorkspaceTableHeader({ labels }: { labels: string[] }) {
    return (
        <thead className="bg-stone-50 text-[0.72rem] font-black tracking-[0.12em] text-stone-500 uppercase">
            <tr>
                {labels.map((label) => (
                    <th key={label} className="px-5 py-4">
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
                <td colSpan={colSpan} className="px-5 py-10 text-center text-sm text-stone-500">
                    {message}
                </td>
            </tr>
        </tbody>
    );
}
