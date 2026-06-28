import CopyButton from '@/components/copy-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    WorkspaceActionCard,
    WorkspaceHero,
    WorkspaceMetricCard,
    WorkspacePanel,
    WorkspaceSectionHeading,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
} from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Cable, CheckCircle2, CloudCog, ExternalLink, KeyRound, Link2, RadioTower, ShieldCheck, Trash2, Watch, Workflow } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'API Access',
        href: '/api-access',
    },
];

interface Viewer {
    name: string;
    email: string;
    primaryRole: string | null;
}

interface AbilityRow {
    name: string;
    description: string;
}

interface TokenRow {
    id: number;
    name: string;
    abilities: string[];
    createdAt: string | null;
    lastUsedAt: string | null;
    expiresAt: string | null;
}

interface ManagedConnection {
    id: number;
    publicId: string;
    provider: string;
    providerLabel: string;
    ownerName: string;
    ownerRole: string | null;
    status: string;
    authType: string;
    lastSyncedAt: string | null;
    grantedScopes: string[];
    ingest: {
        key: string;
        lastFour: string;
        path: string;
        absoluteUrl: string;
    } | null;
}

interface ApiAccessProps {
    viewer: Viewer;
    abilities: AbilityRow[];
    tokens: TokenRow[];
    managedConnections: ManagedConnection[];
    generatedToken: {
        token: string;
        tokenName: string;
        abilities: string[];
        expiresAt: string | null;
    } | null;
    api: {
        baseUrl: string;
        tokenIssueUrl: string;
        tokenTtlDays: number;
        billingWebhookUrl: string;
        sampleTokenCurl: string;
        sampleDashboardCurl: string;
        sampleIngestCurl: string;
    };
}

function humanizeStatus(status: string | null) {
    if (!status) {
        return 'Unknown';
    }

    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['disconnected', 'expired', 'failed'].includes(status)) {
        return 'destructive';
    }

    if (['attention', 'pending'].includes(status)) {
        return 'secondary';
    }

    if (['connected', 'active'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

function codeBlock(code: string) {
    return (
        <pre className="overflow-x-auto rounded-2xl border border-stone-200/75 bg-stone-950 p-4 text-sm leading-6 text-stone-100">
            <code>{code}</code>
        </pre>
    );
}

export default function ApiAccess({ viewer, abilities, tokens, managedConnections, generatedToken, api }: ApiAccessProps) {
    const page = usePage<SharedData>();
    const selectedAbilityNames = abilities.map((ability) => ability.name);
    const { data, setData, post, processing, errors } = useForm<{ token_name: string; abilities: string[] }>({
        token_name: `${viewer.primaryRole ?? 'user'}-integration`,
        abilities: selectedAbilityNames,
    });
    const flashSuccess = page.props.flash?.success;
    const ingestConnections = managedConnections.filter((connection) => connection.ingest);
    const oauthConnections = managedConnections.filter((connection) => connection.authType === 'oauth');

    const toggleAbility = (ability: string, checked: boolean) => {
        const next = checked ? [...data.abilities, ability] : data.abilities.filter((entry) => entry !== ability);
        setData('abilities', Array.from(new Set(next)));
    };

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('api-access.tokens.store'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="API Access" />

            <div className="flex h-full flex-1 flex-col gap-6 bg-white py-8">
                <WorkspaceHero
                    eyebrow="Developer surface"
                    title="API access that normal humans can actually use."
                    description="Bearer tokens, ingest endpoints, Stripe webhook targets, and copy-ready examples live here so integrations stop depending on random messages and memory."
                    badges={[viewer.email, humanizeStatus(viewer.primaryRole), `${api.tokenTtlDays} day token TTL`]}
                    actions={
                        <>
                            <Button asChild size="lg" className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                                <Link href="/wearables">Open wearables</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/memberships">Open billing</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white/80">
                                <Link href="/dashboard">Back to dashboard</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">API base</p>
                                <p className="mt-3 text-sm font-semibold tracking-tight break-all text-stone-950">{api.baseUrl}</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Webhook target</p>
                                <p className="mt-3 text-sm font-semibold tracking-tight break-all text-stone-950">{api.billingWebhookUrl}</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Integration split</p>
                                <p className="mt-3 text-sm text-stone-600">
                                    Bearer tokens hit `/api/v1/*`. Ingest keys hit device-specific ingest URLs.
                                </p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Allowed abilities"
                        value={abilities.length.toString()}
                        note="These are the scopes this account is allowed to request."
                        icon={ShieldCheck}
                    />
                    <WorkspaceMetricCard
                        title="Active tokens"
                        value={tokens.length.toString()}
                        note="Keep the token list tight. A pile of forgotten keys is how dumb security stories start."
                        icon={KeyRound}
                    />
                    <WorkspaceMetricCard
                        title="Managed ingest endpoints"
                        value={ingestConnections.length.toString()}
                        note="Only connections this account can actually manage expose ingest keys here."
                        icon={RadioTower}
                    />
                    <WorkspaceMetricCard
                        title="OAuth connections"
                        value={oauthConnections.length.toString()}
                        note="OAuth providers like WHOOP do not use manual ingest keys."
                        icon={CloudCog}
                    />
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceActionCard
                        title="Dashboard API"
                        href="/dashboard"
                        note="Use the app dashboard while you compare its JSON equivalent in API v1."
                        icon={Cable}
                    />
                    <WorkspaceActionCard
                        title="Wearable ingest"
                        href="/wearables"
                        note="Manage device connections, OAuth providers, and ingestion coverage."
                        icon={Watch}
                    />
                    <WorkspaceActionCard
                        title="Billing endpoints"
                        href="/memberships"
                        note="Checkout, portal, and webhook paths are tied back to the billing workspace."
                        icon={Workflow}
                    />
                    <WorkspaceActionCard
                        title="Contact docs"
                        href="/contact"
                        note="The public site still has the human fallback when integrations hit a wall."
                        icon={Link2}
                    />
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Tokens"
                        title="Create bearer tokens without leaving the product."
                        description="This gives you a proper token panel instead of making everyone hit `/api/v1/auth/tokens` by hand for basic integration work."
                    />
                    <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                        <WorkspacePanel
                            title="Create token"
                            description="Select the scopes you actually need. Defaulting to everything forever is lazy."
                            contentClassName="space-y-5"
                        >
                            {flashSuccess && (
                                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                    {flashSuccess}
                                </div>
                            )}

                            {generatedToken && (
                                <div className="rounded-[1.5rem] border border-emerald-200 bg-emerald-50/80 p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-emerald-900">Plain text token</p>
                                            <p className="mt-1 text-sm leading-6 text-emerald-800">
                                                This is the only time the raw token will be shown. Lose it and you generate another one.
                                            </p>
                                        </div>
                                        <CopyButton value={generatedToken.token} label="Copy token" className="border-emerald-300 bg-white" />
                                    </div>
                                    <div className="mt-4 rounded-2xl border border-emerald-200 bg-white/80 p-4">
                                        <p className="font-mono text-sm break-all text-stone-900">{generatedToken.token}</p>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <Badge variant="outline">{generatedToken.tokenName}</Badge>
                                        {generatedToken.abilities.map((ability) => (
                                            <Badge key={ability} variant="outline">
                                                {ability}
                                            </Badge>
                                        ))}
                                        {generatedToken.expiresAt && <Badge variant="outline">Expires {generatedToken.expiresAt}</Badge>}
                                    </div>
                                </div>
                            )}

                            <form className="space-y-5" onSubmit={submit}>
                                <div className="grid gap-2">
                                    <Label htmlFor="token_name">Token name</Label>
                                    <Input
                                        id="token_name"
                                        value={data.token_name}
                                        onChange={(event) => setData('token_name', event.target.value)}
                                        placeholder="ios-app or partner-sync"
                                    />
                                    {errors.token_name && <p className="text-destructive text-sm">{errors.token_name}</p>}
                                </div>

                                <div className="space-y-3">
                                    <div>
                                        <p className="text-sm font-medium text-stone-950">Abilities</p>
                                        <p className="text-sm leading-6 text-stone-600">
                                            These are role-filtered already. You cannot request scopes your account should not have.
                                        </p>
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {abilities.map((ability) => {
                                            const checked = data.abilities.includes(ability.name);

                                            return (
                                                <label
                                                    key={ability.name}
                                                    className="flex items-start gap-3 rounded-2xl border border-stone-200/75 bg-stone-50/70 p-4"
                                                >
                                                    <Checkbox
                                                        checked={checked}
                                                        onCheckedChange={(state) => toggleAbility(ability.name, state === true)}
                                                    />
                                                    <div className="space-y-1">
                                                        <p className="text-sm font-medium text-stone-950">{ability.name}</p>
                                                        <p className="text-sm leading-6 text-stone-600">{ability.description}</p>
                                                    </div>
                                                </label>
                                            );
                                        })}
                                    </div>
                                    {errors.abilities && <p className="text-destructive text-sm">{errors.abilities}</p>}
                                </div>

                                <div className="flex flex-wrap gap-3">
                                    <Button type="submit" disabled={processing}>
                                        <KeyRound className="size-4" />
                                        Create token
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => setData('abilities', selectedAbilityNames)}>
                                        <CheckCircle2 className="size-4" />
                                        Select all allowed
                                    </Button>
                                </div>
                            </form>
                        </WorkspacePanel>

                        <WorkspacePanel
                            title="Token inventory"
                            description="Revoke the junk you no longer need. Dead tokens should not linger because nobody felt like cleaning them up."
                            contentClassName="space-y-3"
                        >
                            <WorkspaceTable minWidth="min-w-[760px]">
                                <WorkspaceTableHeader labels={['Token', 'Abilities', 'Created', 'Last used', 'Expires', 'Actions']} />
                                {tokens.length === 0 ? (
                                    <WorkspaceTableEmpty message="No personal access tokens exist for this account yet." colSpan={6} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {tokens.map((token) => (
                                            <tr key={token.id} className="align-top transition-colors hover:bg-stone-50/80">
                                                <td className="px-5 py-4 font-semibold text-stone-950">{token.name}</td>
                                                <td className="px-5 py-4">
                                                    <div className="flex max-w-[18rem] flex-wrap gap-1.5">
                                                        {token.abilities.map((ability) => (
                                                            <Badge key={ability} variant="outline">
                                                                {ability}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-5 py-4 text-sm text-stone-700">{token.createdAt ?? 'Unknown'}</td>
                                                <td className="px-5 py-4 text-sm text-stone-700">{token.lastUsedAt ?? 'Never'}</td>
                                                <td className="px-5 py-4 text-sm text-stone-700">{token.expiresAt ?? 'Never'}</td>
                                                <td className="px-5 py-4">
                                                    <Link
                                                        href={route('api-access.tokens.destroy', token.id)}
                                                        method="delete"
                                                        as="button"
                                                        className="inline-flex"
                                                    >
                                                        <Button type="button" variant="outline" size="sm">
                                                            <Trash2 className="size-4" />
                                                            Revoke
                                                        </Button>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </WorkspacePanel>
                    </div>
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Ingest"
                        title="Device endpoints and keys in one place."
                        description="This is the simple path for normalized device posting. Add the public id, add the ingest key, send JSON, move on."
                    />
                    <div className="grid gap-4 xl:grid-cols-[1.08fr_0.92fr]">
                        <WorkspacePanel
                            title="Managed device connections"
                            description="Ingest keys are only shown for connections this account can actually manage."
                            contentClassName="space-y-3"
                        >
                            <WorkspaceTable minWidth="min-w-[1040px]">
                                <WorkspaceTableHeader labels={['Provider', 'Owner', 'Status', 'Auth', 'Last sync', 'Scopes', 'Ingest', 'Copy']} />
                                {managedConnections.length === 0 ? (
                                    <WorkspaceTableEmpty message="No manageable device connections are available yet." colSpan={8} />
                                ) : (
                                    <tbody className="divide-y divide-stone-100">
                                        {managedConnections.map((connection) => (
                                            <tr key={connection.id} className="align-top transition-colors hover:bg-stone-50/80">
                                                <td className="px-5 py-4">
                                                    <p className="font-semibold text-stone-950">{connection.providerLabel}</p>
                                                    <p className="mt-1 text-xs text-stone-500">{connection.publicId}</p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-stone-950">{connection.ownerName}</p>
                                                    <p className="mt-1 text-xs text-stone-500">{humanizeStatus(connection.ownerRole)}</p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <Badge variant={statusVariant(connection.status)}>{humanizeStatus(connection.status)}</Badge>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <Badge variant="outline">{connection.authType === 'oauth' ? 'OAuth' : 'Ingest key'}</Badge>
                                                </td>
                                                <td className="px-5 py-4 text-sm text-stone-700">{connection.lastSyncedAt ?? 'Never'}</td>
                                                <td className="px-5 py-4">
                                                    <p className="line-clamp-2 max-w-[14rem] text-xs text-stone-500">
                                                        {connection.grantedScopes.length ? connection.grantedScopes.join(', ') : 'No scopes'}
                                                    </p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    {connection.ingest ? (
                                                        <div className="space-y-1">
                                                            <p className="font-mono text-xs text-stone-700">****{connection.ingest.lastFour}</p>
                                                            <p className="line-clamp-2 max-w-[14rem] text-xs break-all text-stone-500">
                                                                {connection.ingest.absoluteUrl}
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-stone-500">OAuth managed</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-4">
                                                    {connection.ingest ? (
                                                        <div className="flex flex-col gap-2">
                                                            <CopyButton value={connection.ingest.key} label="Copy key" />
                                                            <CopyButton value={connection.ingest.absoluteUrl} label="Copy URL" variant="secondary" />
                                                            <CopyButton
                                                                value={`curl -X POST "${connection.ingest.absoluteUrl}" -H "X-Throughline-Key: ${connection.ingest.key}" -H "Content-Type: application/json" -d '{"metric_date":"2026-06-20","metrics":{"readiness_score":82,"sleep_minutes":445,"strain_score":13.4}}'`}
                                                                label="Copy curl"
                                                                variant="secondary"
                                                            />
                                                        </div>
                                                    ) : (
                                                        <Badge variant="outline">No key</Badge>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                )}
                            </WorkspaceTable>
                        </WorkspacePanel>

                        <WorkspacePanel
                            title="Copy-ready examples"
                            description="These are the requests people keep asking for. Now they live on the page instead of in your chat history."
                            contentClassName="space-y-5"
                        >
                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="font-medium text-stone-950">Issue a bearer token</p>
                                    <CopyButton value={api.sampleTokenCurl} label="Copy curl" />
                                </div>
                                {codeBlock(api.sampleTokenCurl)}
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="font-medium text-stone-950">Fetch dashboard JSON</p>
                                    <CopyButton value={api.sampleDashboardCurl} label="Copy curl" />
                                </div>
                                {codeBlock(api.sampleDashboardCurl)}
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="font-medium text-stone-950">Post normalized device metrics</p>
                                    <CopyButton value={api.sampleIngestCurl} label="Copy curl" />
                                </div>
                                {codeBlock(api.sampleIngestCurl)}
                            </div>

                            <div className="rounded-[1.5rem] border border-stone-200/75 bg-stone-50/70 p-4">
                                <p className="font-medium text-stone-950">Webhook endpoint</p>
                                <p className="mt-2 text-sm leading-6 break-all text-stone-600">{api.billingWebhookUrl}</p>
                                <div className="mt-3 flex gap-2">
                                    <CopyButton value={api.billingWebhookUrl} label="Copy URL" />
                                    <Button asChild variant="outline" size="sm">
                                        <a href={api.billingWebhookUrl} target="_blank" rel="noreferrer">
                                            <ExternalLink className="size-4" />
                                            Open
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </WorkspacePanel>
                    </div>
                </section>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Map"
                        title="What each integration path is for."
                        description="This is the shortest explanation of the moving parts, which is all most people should need."
                    />
                    <WorkspacePanel title="Integration map" description="Use the right credential for the right job. Mixing these up is how APIs become a mess.">
                        <WorkspaceTable minWidth="min-w-[920px]">
                            <WorkspaceTableHeader labels={['Path', 'Use for', 'Credential', 'Notes']} />
                            <tbody className="divide-y divide-stone-100">
                                <tr className="align-top hover:bg-stone-50/80">
                                    <td className="px-5 py-4 font-semibold text-stone-950">Bearer token</td>
                                    <td className="px-5 py-4 text-sm leading-6 text-stone-600">Your app, admin scripts, and partner reads against API v1.</td>
                                    <td className="px-5 py-4 font-mono text-xs text-stone-700">Authorization: Bearer {'{YOUR_TOKEN}'}</td>
                                    <td className="px-5 py-4 text-sm leading-6 text-stone-600">Controlled by token abilities such as training:read or progress:write.</td>
                                </tr>
                                <tr className="align-top hover:bg-stone-50/80">
                                    <td className="px-5 py-4 font-semibold text-stone-950">Ingest key</td>
                                    <td className="px-5 py-4 text-sm leading-6 text-stone-600">Normalized device posting into one specific device connection.</td>
                                    <td className="px-5 py-4 font-mono text-xs text-stone-700">X-Throughline-Key: {'{INGEST_KEY}'}</td>
                                    <td className="px-5 py-4 text-sm leading-6 text-stone-600">This is not a user login token. Keep it scoped to ingestion.</td>
                                </tr>
                                <tr className="align-top hover:bg-stone-50/80">
                                    <td className="px-5 py-4 font-semibold text-stone-950">Webhook</td>
                                    <td className="px-5 py-4 text-sm leading-6 text-stone-600">Stripe event delivery into billing state.</td>
                                    <td className="px-5 py-4 font-mono text-xs text-stone-700">Stripe signature</td>
                                    <td className="px-5 py-4 text-sm leading-6 text-stone-600">Signature comes from Stripe, not from your app users.</td>
                                </tr>
                            </tbody>
                        </WorkspaceTable>
                    </WorkspacePanel>
                </section>
            </div>
        </AppLayout>
    );
}
