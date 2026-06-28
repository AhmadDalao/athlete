import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { WorkspaceHero, WorkspaceMetricCard, WorkspacePanel, WorkspaceSectionHeading } from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FileText, Mail, Save, Settings2, SlidersHorizontal } from 'lucide-react';
import { type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System settings',
        href: '/admin/system-settings',
    },
];

interface SettingRow {
    key: string;
    label: string;
    help: string;
    value: string;
    default: string;
}

interface SettingGroup {
    name: string;
    settings: SettingRow[];
}

interface MailRuntime {
    mailer: string | null;
    host: string | null;
    port: string | number | null;
    encryption: string | null;
    fromAddress: string | null;
    fromName: string | null;
}

interface SystemSettingsProps {
    groups: SettingGroup[];
    mailRuntime: MailRuntime;
}

interface SettingsFormData {
    settings: Record<string, string>;
}

function inputFor(setting: SettingRow, value: string, update: (value: string) => void) {
    const longText = setting.key.includes('headline') || setting.key.includes('notice') || setting.key.includes('note');

    if (longText) {
        return <Textarea id={setting.key} value={value} onChange={(event) => update(event.target.value)} rows={4} />;
    }

    return <Input id={setting.key} value={value} onChange={(event) => update(event.target.value)} />;
}

function groupIcon(name: string) {
    if (name === 'Mail') {
        return Mail;
    }

    if (name.includes('text')) {
        return FileText;
    }

    if (name.includes('System')) {
        return SlidersHorizontal;
    }

    return Settings2;
}

export default function SystemSettings({ groups, mailRuntime }: SystemSettingsProps) {
    const initialSettings = Object.fromEntries(groups.flatMap((group) => group.settings).map((setting) => [setting.key, setting.value ?? '']));
    const { data, setData, patch, processing, errors } = useForm<SettingsFormData>({
        settings: initialSettings,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(route('admin.system-settings.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System settings" />

            <form className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6" onSubmit={submit}>
                <WorkspaceHero
                    eyebrow="Admin system control"
                    title="Control the product copy and operational identity from one page."
                    description="This is the safe admin layer for public text, sender identity, support routing, and platform notices. SMTP secrets still belong in environment variables."
                    badges={['Admin only', `${groups.length} setting groups`, 'Light mode only']}
                    actions={
                        <>
                            <Button type="submit" size="lg" disabled={processing} className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Save className="size-4" />
                                Save settings
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white text-stone-900">
                                <Link href={route('admin.control-center')}>Control center</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="space-y-3">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Runtime mailer</p>
                                <p className="mt-3 text-lg font-semibold text-stone-950">{mailRuntime.mailer ?? 'Not configured'}</p>
                                <p className="mt-2 text-sm leading-6 text-stone-600">
                                    {mailRuntime.host ?? 'No SMTP host'} {mailRuntime.port ? `:${mailRuntime.port}` : ''}
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Current from</p>
                                <p className="mt-3 text-sm font-semibold text-stone-950">{mailRuntime.fromName ?? 'Unknown sender'}</p>
                                <p className="mt-2 text-sm break-all text-stone-600">{mailRuntime.fromAddress ?? 'No from address'}</p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-3">
                    <WorkspaceMetricCard
                        title="Setting groups"
                        value={groups.length.toString()}
                        note="General, mail, copy, and system controls."
                        icon={Settings2}
                    />
                    <WorkspaceMetricCard
                        title="Mailer"
                        value={mailRuntime.mailer ?? 'none'}
                        note="Transport still comes from .env for safety."
                        icon={Mail}
                    />
                    <WorkspaceMetricCard
                        title="Editable text"
                        value="Live"
                        note="Homepage hero copy now reads from these settings."
                        icon={FileText}
                    />
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Controls"
                        title="Editable system settings."
                        description="Change what should be safely editable by admins. Do not put SMTP passwords or API secrets here."
                    />

                    {groups.map((group) => {
                        const Icon = groupIcon(group.name);

                        return (
                            <WorkspacePanel
                                key={group.name}
                                title={group.name}
                                description={`${group.settings.length} setting(s) in this section.`}
                                contentClassName="grid gap-4 lg:grid-cols-2"
                            >
                                {group.settings.map((setting) => (
                                    <div key={setting.key} className="rounded-2xl border border-stone-200/75 bg-white/90 p-4">
                                        <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <Label htmlFor={setting.key}>{setting.label}</Label>
                                                <p className="mt-1 text-sm leading-6 text-stone-500">{setting.help}</p>
                                            </div>
                                            <Badge variant="outline" className="shrink-0">
                                                <Icon className="mr-1 size-3" />
                                                {setting.key}
                                            </Badge>
                                        </div>
                                        {inputFor(setting, data.settings[setting.key] ?? '', (value) =>
                                            setData('settings', {
                                                ...data.settings,
                                                [setting.key]: value,
                                            }),
                                        )}
                                        <p className="mt-2 text-xs leading-5 text-stone-400">Default: {setting.default || 'Blank'}</p>
                                        <InputError message={(errors as Record<string, string | undefined>)[`settings.${setting.key}`]} />
                                    </div>
                                ))}
                            </WorkspacePanel>
                        );
                    })}
                </section>
            </form>
        </AppLayout>
    );
}
