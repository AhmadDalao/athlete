import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, MailCheck, PhoneCall, Search, Shield, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin users',
        href: '/admin/users',
    },
];

interface Option {
    value: string;
    label: string;
}

interface UserRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    primaryGoal: string | null;
    preferredContactMethod: string | null;
    registrationChannel: string | null;
    emailVerifiedAt: string | null;
    phoneVerifiedAt: string | null;
    roles: string[];
    primaryRole: string | null;
    membershipsCount: number;
    deviceConnectionsCount: number;
    currentMembership: {
        status: string;
        planName: string;
        daysRemaining: number | null;
    } | null;
    activeAthleteCount: number;
    createdAt: string | null;
}

interface UserPaginator {
    data: UserRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface AdminUsersPageProps {
    filters: {
        q: string | null;
        role: string | null;
        channel: string | null;
    };
    summary: {
        totalUsers: number;
        admins: number;
        coaches: number;
        athletes: number;
        phoneFirst: number;
        emailVerified: number;
        newThisMonth: number;
    };
    users: UserPaginator;
    roleOptions: Option[];
    channelOptions: Option[];
}

interface UserUpdateFormData {
    name: string;
    email: string;
    phone: string;
    primary_goal: string;
    preferred_contact_method: string;
    registration_channel: string;
    roles: string[];
}

function humanizeStatus(status: string) {
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDays(days: number | null) {
    if (days === null) {
        return 'No end date';
    }

    if (days === 0) {
        return 'Ends today';
    }

    if (days === 1) {
        return '1 day left';
    }

    return `${days} days left`;
}

function badgeVariantForMembership(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (['past_due', 'cancelled', 'expired'].includes(status)) {
        return 'destructive';
    }

    if (status === 'grace') {
        return 'secondary';
    }

    if (['trialing', 'active'].includes(status)) {
        return 'default';
    }

    return 'outline';
}

function MetricCard({
    title,
    value,
    note,
    icon: Icon,
}: {
    title: string;
    value: string;
    note: string;
    icon: typeof Users;
}) {
    return (
        <Card className="border-sidebar-border/70">
            <CardHeader className="flex flex-row items-start justify-between space-y-0">
                <div>
                    <CardDescription>{title}</CardDescription>
                    <CardTitle className="mt-3 text-3xl font-semibold tracking-tight">{value}</CardTitle>
                </div>
                <div className="rounded-full bg-primary/10 p-2 text-primary">
                    <Icon className="size-4" />
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-sm leading-6 text-muted-foreground">{note}</p>
            </CardContent>
        </Card>
    );
}

function UserEditDialog({ user, roleOptions, channelOptions }: { user: UserRow; roleOptions: Option[]; channelOptions: Option[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm<UserUpdateFormData>({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        primary_goal: user.primaryGoal ?? '',
        preferred_contact_method: user.preferredContactMethod ?? 'email',
        registration_channel: user.registrationChannel ?? 'email',
        roles: user.roles,
    });

    const toggleRole = (role: string, checked: boolean) => {
        setData(
            'roles',
            checked ? [...data.roles, role] : data.roles.filter((value) => value !== role),
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(route('admin.users.update', user.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Edit user
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit {user.name}</DialogTitle>
                    <DialogDescription>Clean up roles, contact settings, and onboarding metadata from the UI instead of guessing in phpMyAdmin.</DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`user-name-${user.id}`}>Name</Label>
                            <Input id={`user-name-${user.id}`} value={data.name} onChange={(event) => setData('name', event.target.value)} />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`user-email-${user.id}`}>Email</Label>
                            <Input id={`user-email-${user.id}`} value={data.email} onChange={(event) => setData('email', event.target.value)} />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor={`user-phone-${user.id}`}>Phone</Label>
                            <Input id={`user-phone-${user.id}`} value={data.phone} onChange={(event) => setData('phone', event.target.value)} />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`user-contact-${user.id}`}>Preferred contact</Label>
                            <Select value={data.preferred_contact_method} onValueChange={(value) => setData('preferred_contact_method', value)}>
                                <SelectTrigger id={`user-contact-${user.id}`}>
                                    <SelectValue placeholder="Preferred contact" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="email">Email</SelectItem>
                                    <SelectItem value="phone">Phone</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.preferred_contact_method} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`user-channel-${user.id}`}>Registration channel</Label>
                            <Select value={data.registration_channel} onValueChange={(value) => setData('registration_channel', value)}>
                                <SelectTrigger id={`user-channel-${user.id}`}>
                                    <SelectValue placeholder="Registration channel" />
                                </SelectTrigger>
                                <SelectContent>
                                    {channelOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.registration_channel} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`user-goal-${user.id}`}>Primary goal</Label>
                        <Textarea
                            id={`user-goal-${user.id}`}
                            value={data.primary_goal}
                            onChange={(event) => setData('primary_goal', event.target.value)}
                            placeholder="What is this user actually trying to do?"
                        />
                        <InputError message={errors.primary_goal} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Roles</Label>
                        <div className="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-3">
                            {roleOptions.map((role) => {
                                const checked = data.roles.includes(role.value);

                                return (
                                    <label key={role.value} className="flex items-center gap-3 text-sm font-medium">
                                        <Checkbox checked={checked} onCheckedChange={(value) => toggleRole(role.value, value === true)} />
                                        <span>{role.label}</span>
                                    </label>
                                );
                            })}
                        </div>
                        <InputError message={errors.roles} />
                    </div>

                    <div className="flex justify-end">
                        <Button disabled={processing} asChild>
                            <button type="submit">Save user</button>
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function AdminUsersIndex({ filters, summary, users, roleOptions, channelOptions }: AdminUsersPageProps) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [role, setRole] = useState(filters.role ?? 'all');
    const [channel, setChannel] = useState(filters.channel ?? 'all');

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            route('admin.users.index'),
            {
                q: search || undefined,
                role: role === 'all' ? undefined : role,
                channel: channel === 'all' ? undefined : channel,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin users" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Card className="border-sidebar-border/70 bg-linear-to-br from-background via-background to-muted/40">
                    <CardHeader>
                        <CardTitle className="text-3xl">User control</CardTitle>
                        <CardDescription className="max-w-3xl leading-6">
                            Admin-only control over roles, onboarding data, and contact preferences. This is the part where the platform stops being a pile of accounts.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <MetricCard
                            title="Platform users"
                            value={summary.totalUsers.toString()}
                            note={`${summary.admins} admins, ${summary.coaches} coaches, and ${summary.athletes} athletes.`}
                            icon={Users}
                        />
                        <MetricCard
                            title="Email verified"
                            value={summary.emailVerified.toString()}
                            note="Accounts with verified email status kept intact."
                            icon={MailCheck}
                        />
                        <MetricCard
                            title="Phone-first users"
                            value={summary.phoneFirst.toString()}
                            note="Accounts that prefer phone as the primary contact path."
                            icon={PhoneCall}
                        />
                        <MetricCard
                            title="New this month"
                            value={summary.newThisMonth.toString()}
                            note="Fresh accounts created since the start of the month."
                            icon={Shield}
                        />
                    </CardContent>
                </Card>

                <Card className="border-sidebar-border/70">
                    <CardHeader>
                        <CardTitle className="text-lg">Find users fast</CardTitle>
                        <CardDescription>Filter by search, role, or signup channel before the list gets ugly.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="grid gap-4 lg:grid-cols-[1fr_0.26fr_0.26fr_auto]" onSubmit={applyFilters}>
                            <div className="grid gap-2">
                                <Label htmlFor="user-search">Search</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="user-search"
                                        className="pl-9"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Name, email, phone, goal"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="user-role-filter">Role</Label>
                                <Select value={role} onValueChange={setRole}>
                                    <SelectTrigger id="user-role-filter">
                                        <SelectValue placeholder="All roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All roles</SelectItem>
                                        {roleOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="user-channel-filter">Channel</Label>
                                <Select value={channel} onValueChange={setChannel}>
                                    <SelectTrigger id="user-channel-filter">
                                        <SelectValue placeholder="All channels" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All channels</SelectItem>
                                        {channelOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end gap-2">
                                <Button type="submit">Apply</Button>
                                <Button variant="outline" asChild>
                                    <Link href={route('admin.users.index')} preserveScroll>
                                        Reset
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <section className="space-y-4">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">User queue</h2>
                        <p className="text-sm leading-6 text-muted-foreground">{users.total} record(s) match the current filter set.</p>
                    </div>

                    <Card className="border-sidebar-border/70">
                        <CardContent className="space-y-3 p-4">
                            {users.data.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center">
                                    <p className="font-medium">No users match this filter.</p>
                                    <p className="mt-2 text-sm text-muted-foreground">Either the filters are tight or the platform is still tiny. Both are fine.</p>
                                </div>
                            ) : (
                                users.data.map((user) => (
                                    <div key={user.id} className="rounded-2xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/30">
                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div className="space-y-2">
                                                <div>
                                                    <p className="font-medium">{user.name}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {user.email}
                                                        {user.phone ? ` · ${user.phone}` : ''}
                                                    </p>
                                                </div>

                                                <div className="flex flex-wrap gap-2">
                                                    {user.roles.map((roleName) => (
                                                        <Badge key={roleName} variant="outline">
                                                            {humanizeStatus(roleName)}
                                                        </Badge>
                                                    ))}
                                                    {user.emailVerifiedAt && <Badge variant="default">Email verified</Badge>}
                                                    {user.phoneVerifiedAt && <Badge variant="default">Phone verified</Badge>}
                                                    <Badge variant="outline">{humanizeStatus(user.registrationChannel ?? 'email')}</Badge>
                                                </div>
                                            </div>

                                            <UserEditDialog user={user} roleOptions={roleOptions} channelOptions={channelOptions} />
                                        </div>

                                        <div className="mt-4 grid gap-3 md:grid-cols-4">
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Primary goal</p>
                                                <p className="mt-2 text-sm font-medium">{user.primaryGoal ?? 'Not set'}</p>
                                            </div>
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Preferred contact</p>
                                                <p className="mt-2 text-sm font-medium">{humanizeStatus(user.preferredContactMethod ?? 'email')}</p>
                                            </div>
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Memberships / devices</p>
                                                <p className="mt-2 text-sm font-medium">
                                                    {user.membershipsCount} memberships · {user.deviceConnectionsCount} devices
                                                </p>
                                            </div>
                                            <div className="rounded-xl border border-sidebar-border/70 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Created</p>
                                                <p className="mt-2 text-sm font-medium">{user.createdAt ?? 'Unknown'}</p>
                                            </div>
                                        </div>

                                        <div className="mt-4 grid gap-3 md:grid-cols-2">
                                            <div className="rounded-xl border border-sidebar-border/70 bg-background/80 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Current membership</p>
                                                {user.currentMembership ? (
                                                    <div className="mt-2 flex flex-wrap items-center gap-2">
                                                        <Badge variant={badgeVariantForMembership(user.currentMembership.status)}>
                                                            {humanizeStatus(user.currentMembership.status)}
                                                        </Badge>
                                                        <Badge variant="outline">{user.currentMembership.planName}</Badge>
                                                        <span className="text-sm text-muted-foreground">{formatDays(user.currentMembership.daysRemaining)}</span>
                                                    </div>
                                                ) : (
                                                    <p className="mt-2 text-sm text-muted-foreground">No current membership on record.</p>
                                                )}
                                            </div>

                                            <div className="rounded-xl border border-sidebar-border/70 bg-background/80 p-3">
                                                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">Coach roster impact</p>
                                                <p className="mt-2 text-sm font-medium">
                                                    {user.activeAthleteCount > 0 ? `${user.activeAthleteCount} active athlete assignments.` : 'No active roster assignments.'}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-between">
                        <Button variant="outline" asChild disabled={!users.prev_page_url}>
                            {users.prev_page_url ? (
                                <Link href={users.prev_page_url} preserveScroll>
                                    <ArrowLeft className="mr-2 size-4" />
                                    Previous
                                </Link>
                            ) : (
                                <span>
                                    <ArrowLeft className="mr-2 size-4" />
                                    Previous
                                </span>
                            )}
                        </Button>

                        <p className="text-sm text-muted-foreground">
                            Page {users.current_page} of {users.last_page}
                        </p>

                        <Button variant="outline" asChild disabled={!users.next_page_url}>
                            {users.next_page_url ? (
                                <Link href={users.next_page_url} preserveScroll>
                                    Next
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            ) : (
                                <span>
                                    Next
                                    <ArrowRight className="ml-2 size-4" />
                                </span>
                            )}
                        </Button>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
