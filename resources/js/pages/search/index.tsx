import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { WorkspacePanel } from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Search',
        href: '/search',
    },
];

interface SearchItem {
    title: string;
    meta: string;
    href: string;
    kind: string;
}

interface SearchSection {
    title: string;
    description: string;
    items: SearchItem[];
}

interface SearchProps {
    query: string;
    sections: SearchSection[];
}

export default function SearchIndex({ query, sections }: SearchProps) {
    const totalResults = sections.reduce((sum, section) => sum + section.items.length, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Search" />

            <div className="flex h-full flex-1 flex-col gap-6 bg-white py-8">
                <div className="flex flex-wrap items-end justify-between gap-4 border-b border-stone-200 pb-5">
                    <div>
                        <p className="text-sm font-semibold text-stone-500">Find anything fast</p>
                        <h1 className="font-['Space_Grotesk'] text-4xl font-bold tracking-[-0.05em] text-stone-950">Search</h1>
                    </div>
                    <Badge variant="outline" className="border-stone-200 bg-white text-stone-700">
                        {query ? `${totalResults} result(s)` : 'Quick links'}
                    </Badge>
                </div>

                <form action={route('search.index')} method="get" className="rounded-[1.35rem] border border-stone-200 bg-white p-4">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                        <label className="grid gap-2 text-sm font-semibold text-stone-800">
                            Search the workspace
                            <Input name="q" defaultValue={query} placeholder="Athlete name, email, plan, WHOOP, program, alert..." />
                        </label>
                        <Button type="submit" className="rounded-xl bg-stone-950 text-white hover:bg-stone-800">
                            <Search className="size-4" />
                            Search
                        </Button>
                    </div>
                </form>

                <div className="grid gap-4">
                    {sections.map((section) => (
                        <WorkspacePanel key={section.title} title={section.title} description={section.description} contentClassName="space-y-3">
                            {section.items.length === 0 ? (
                                <p className="rounded-2xl border border-dashed border-stone-200 bg-white px-4 py-6 text-sm text-stone-500">
                                    No matching records in this section.
                                </p>
                            ) : (
                                section.items.map((item) => (
                                    <Link
                                        key={`${section.title}-${item.kind}-${item.title}-${item.href}`}
                                        href={item.href}
                                        className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-white p-4 transition hover:border-stone-300 hover:bg-stone-50"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold text-stone-950">{item.title}</p>
                                                <Badge variant="outline">{item.kind}</Badge>
                                            </div>
                                            <p className="mt-1 text-sm leading-6 text-stone-600">{item.meta}</p>
                                        </div>
                                        <span className="inline-flex items-center text-sm font-semibold text-stone-900">
                                            Open
                                            <ArrowRight className="ml-2 size-4" />
                                        </span>
                                    </Link>
                                ))
                            )}
                        </WorkspacePanel>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
