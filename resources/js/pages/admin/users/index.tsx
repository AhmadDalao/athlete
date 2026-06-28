import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { WorkspaceHero, WorkspaceMetricCard, WorkspacePanel, WorkspaceSectionHeading } from '@/components/workspace-primitives';
import { useAutoFilter } from '@/hooks/use-auto-filter';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, MailCheck, PhoneCall, Search, Shield, UserPlus, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const adminPrimaryButtonClass = 'rounded-full bg-stone-950 text-white hover:bg-stone-800';
const adminSecondaryButtonClass = 'rounded-full border-stone-300 bg-white text-stone-900 hover:bg-stone-50';

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

interface PermissionOption {
    key: string;
    description: string;
}

interface PermissionGroup {
    key: string;
    label: string;
    permissions: PermissionOption[];
}

interface UserRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    primaryGoal: string | null;
    position: string | null;
    preferredContactMethod: string | null;
    registrationChannel: string | null;
    emailVerifiedAt: string | null;
    phoneVerifiedAt: string | null;
    roles: string[];
    primaryRole: string | null;
    permissions: string[];
    permissionCount: number;
    membershipsCount: number;
    deviceConnectionsCount: number;
    currentMembership: {
        status: string;
        planName: string;
        startsAt: string | null;
        renewsAt: string | null;
        endsAt: string | null;
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
        owners: number;
        admins: number;
        coaches: number;
        athletes: number;
        phoneFirst: number;
        emailVerified: number;
        newThisMonth: number;
    };
    users: UserPaginator;
    roleOptions: Option[];
    permissionGroups: PermissionGroup[];
    canManageOwner: boolean;
    channelOptions: Option[];
}

interface UserUpdateFormData {
    name: string;
    email: string;
    phone: string;
    primary_goal: string;
    position: string;
    preferred_contact_method: string;
    registration_channel: string;
    roles: string[];
    permissions: string[];
}

interface UserCreateFormData {
    name: string;
    email: string;
    phone: string;
    password: string;
    primary_goal: string;
    position: string;
    preferred_contact_method: string;
    registration_channel: string;
    roles: string[];
    permissions: string[];
    email_verified: boolean;
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
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

function formatDate(value: string | null) {
    return value ?? 'Not set';
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

function userFilterPayload({ search, role, channel }: { search: string; role: string; channel: string }) {
    return {
        q: search.trim() || undefined,
        role: role === 'all' ? undefined : role,
        channel: channel === 'all' ? undefined : channel,
    };
}

function allPermissionKeys(permissionGroups: PermissionGroup[]) {
    return permissionGroups.flatMap((group) => group.permissions.map((permission) => permission.key));
}

function defaultPermissionsForRoles(roles: string[], permissionGroups: PermissionGroup[]) {
    const keys = allPermissionKeys(permissionGroups);

    if (roles.includes('owner') || roles.includes('admin')) {
        return keys;
    }

    const coachDefaults = [
        'dashboard.view',
        'notifications.manage',
        'roster.view',
        'roster.manage',
        'roster.invite',
        'athletes.view',
        'athlete.files.view',
        'athlete.files.manage',
        'training.view',
        'training.manage',
        'progress.view',
        'progress.manage',
        'wearables.view',
        'memberships.view',
        'api_access.manage',
    ];
    const athleteDefaults = [
        'dashboard.view',
        'training.view',
        'training.manage',
        'progress.view',
        'progress.manage',
        'wearables.view',
        'memberships.view',
        'api_access.manage',
    ];

    const defaults = roles.flatMap((role) => (role === 'coach' ? coachDefaults : role === 'athlete' ? athleteDefaults : []));

    return keys.filter((key) => defaults.includes(key));
}

function togglePermission(selected: string[], permission: string, checked: boolean) {
    if (checked) {
        return selected.includes(permission) ? selected : [...selected, permission];
    }

    return selected.filter((value) => value !== permission);
}

function PermissionChecklist({
    permissionGroups,
    selected,
    disabled = false,
    onChange,
    onUseRoleDefaults,
}: {
    permissionGroups: PermissionGroup[];
    selected: string[];
    disabled?: boolean;
    onChange: (permissions: string[]) => void;
    onUseRoleDefaults: () => void;
}) {
    return (
        <div className="grid gap-3">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Label>Permissions</Label>
                    <p className="mt-1 text-xs text-stone-500">
                        These control sidebar access and admin actions. Owner always gets everything.
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" onClick={onUseRoleDefaults} disabled={disabled}>
                    Use role defaults
                </Button>
            </div>

            <div className="grid max-h-[18rem] gap-3 overflow-y-auto rounded-2xl border border-stone-200 bg-stone-50/70 p-3 md:grid-cols-2">
                {permissionGroups.map((group) => (
                    <div key={group.key} className="rounded-xl border border-stone-200 bg-white p-3">
                        <p className="text-xs font-semibold tracking-[0.16em] text-stone-500 uppercase">{group.label}</p>
                        <div className="mt-3 grid gap-2">
                            {group.permissions.map((permission) => (
                                <label key={permission.key} className="flex items-start gap-3 rounded-lg p-2 text-sm hover:bg-stone-50">
                                    <Checkbox
                                        checked={selected.includes(permission.key)}
                                        disabled={disabled}
                                        onCheckedChange={(value) => onChange(togglePermission(selected, permission.key, value === true))}
                                    />
                                    <span>
                                        <span className="block font-medium text-stone-950">{permission.key}</span>
                                        <span className="mt-0.5 block text-xs leading-5 text-stone-500">{permission.description}</span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function UserCreateDialog({
    roleOptions,
    permissionGroups,
    channelOptions,
}: {
    roleOptions: Option[];
    permissionGroups: PermissionGroup[];
    channelOptions: Option[];
}) {
    const [open, setOpen] = useState(false);
    const athleteDefaults = defaultPermissionsForRoles(['athlete'], permissionGroups);
    const { data, setData, post, processing, errors, reset } = useForm<UserCreateFormData>({
        name: '',
        email: '',
        phone: '',
        password: '',
        primary_goal: '',
        position: '',
        preferred_contact_method: 'email',
        registration_channel: 'email',
        roles: ['athlete'],
        permissions: athleteDefaults,
        email_verified: true,
    });

    const toggleRole = (role: string, checked: boolean) => {
        const nextRoles = checked ? [...data.roles, role] : data.roles.filter((value) => value !== role);

        setData({
            ...data,
            roles: nextRoles,
            permissions: defaultPermissionsForRoles(nextRoles, permissionGroups),
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="lg" className={adminPrimaryButtonClass}>
                    <UserPlus className="size-4" />
                    Create user
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Create user or admin</DialogTitle>
                    <DialogDescription>
                        Add coaches, athletes, or admins without going near phpMyAdmin. Use a temporary password and tell the user to reset it.
                    </DialogDescription>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="create-user-name">Name</Label>
                            <Input id="create-user-name" value={data.name} onChange={(event) => setData('name', event.target.value)} />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="create-user-email">Email</Label>
                            <Input id="create-user-email" value={data.email} onChange={(event) => setData('email', event.target.value)} />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="create-user-phone">Phone</Label>
                            <Input id="create-user-phone" value={data.phone} onChange={(event) => setData('phone', event.target.value)} />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="create-user-password">Temporary password</Label>
                            <Input
                                id="create-user-password"
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="create-user-contact">Preferred contact</Label>
                            <Select value={data.preferred_contact_method} onValueChange={(value) => setData('preferred_contact_method', value)}>
                                <SelectTrigger id="create-user-contact">
                                    <SelectValue placeholder="Preferred contact" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="email">Email</SelectItem>
                                    <SelectItem value="phone">Phone</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.preferred_contact_method} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="create-user-channel">Registration channel</Label>
                            <Select value={data.registration_channel} onValueChange={(value) => setData('registration_channel', value)}>
                                <SelectTrigger id="create-user-channel">
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

                        <label className="flex items-center gap-3 rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium">
                            <Checkbox checked={data.email_verified} onCheckedChange={(value) => setData('email_verified', value === true)} />
                            Mark email verified
                        </label>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="create-user-goal">Primary goal</Label>
                        <Textarea
                            id="create-user-goal"
                            value={data.primary_goal}
                            onChange={(event) => setData('primary_goal', event.target.value)}
                            placeholder="What should this user be able to accomplish?"
                        />
                        <InputError message={errors.primary_goal} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="create-user-position">Position / title</Label>
                        <Input
                            id="create-user-position"
                            value={data.position}
                            onChange={(event) => setData('position', event.target.value)}
                            placeholder="Owner / General Manager, Head Coach, Billing Admin"
                        />
                        <InputError message={errors.position} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Roles</Label>
                        <div className="grid gap-3 rounded-xl border border-stone-200 p-4 md:grid-cols-3">
                            {roleOptions.map((roleOption) => {
                                const checked = data.roles.includes(roleOption.value);

                                return (
                                    <label key={roleOption.value} className="flex items-center gap-3 text-sm font-medium">
                                        <Checkbox checked={checked} onCheckedChange={(value) => toggleRole(roleOption.value, value === true)} />
                                        <span>{roleOption.label}</span>
                                    </label>
                                );
                            })}
                        </div>
                        <InputError message={errors.roles} />
                    </div>

                    <PermissionChecklist
                        permissionGroups={permissionGroups}
                        selected={data.roles.includes('owner') ? allPermissionKeys(permissionGroups) : data.permissions}
                        disabled={data.roles.includes('owner')}
                        onChange={(permissions) => setData('permissions', permissions)}
                        onUseRoleDefaults={() => setData('permissions', defaultPermissionsForRoles(data.roles, permissionGroups))}
                    />
                    <InputError message={errors.permissions} />

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Create account
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function UserEditDialog({
    user,
    roleOptions,
    permissionGroups,
    channelOptions,
}: {
    user: UserRow;
    roleOptions: Option[];
    permissionGroups: PermissionGroup[];
    channelOptions: Option[];
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm<UserUpdateFormData>({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        primary_goal: user.primaryGoal ?? '',
        position: user.position ?? '',
        preferred_contact_method: user.preferredContactMethod ?? 'email',
        registration_channel: user.registrationChannel ?? 'email',
        roles: user.roles,
        permissions: user.permissions,
    });

    const toggleRole = (role: string, checked: boolean) => {
        setData('roles', checked ? [...data.roles, role] : data.roles.filter((value) => value !== role));
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
            <DialogContent className="max-h-[90vh] max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Edit {user.name}</DialogTitle>
                    <DialogDescription>
                        Clean up roles, contact settings, and onboarding metadata from the UI instead of guessing in phpMyAdmin.
                    </DialogDescription>
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
                        <Label htmlFor={`user-position-${user.id}`}>Position / title</Label>
                        <Input
                            id={`user-position-${user.id}`}
                            value={data.position}
                            onChange={(event) => setData('position', event.target.value)}
                            placeholder="Owner / General Manager, Head Coach, Billing Admin"
                        />
                        <InputError message={errors.position} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Roles</Label>
                        <div className="border-sidebar-border/70 grid gap-3 rounded-xl border p-4 md:grid-cols-3">
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

                    <PermissionChecklist
                        permissionGroups={permissionGroups}
                        selected={data.roles.includes('owner') ? allPermissionKeys(permissionGroups) : data.permissions}
                        disabled={data.roles.includes('owner')}
                        onChange={(permissions) => setData('permissions', permissions)}
                        onUseRoleDefaults={() => setData('permissions', defaultPermissionsForRoles(data.roles, permissionGroups))}
                    />
                    <InputError message={errors.permissions} />

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

export default function AdminUsersIndex({
    filters,
    summary,
    users,
    roleOptions,
    permissionGroups,
    canManageOwner,
    channelOptions,
}: AdminUsersPageProps) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [role, setRole] = useState(filters.role ?? 'all');
    const [channel, setChannel] = useState(filters.channel ?? 'all');
    const baseRoute = route('admin.users.index');
    const filterPayload = userFilterPayload({ search, role, channel });

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(baseRoute, filterPayload, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    useAutoFilter({ url: baseRoute, payload: filterPayload, only: ['filters', 'summary', 'users'] });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin users" />

            <div className="flex h-full flex-1 flex-col gap-8 rounded-[2rem] border border-stone-200/80 bg-[#faf9f6] p-4 md:p-6">
                <WorkspaceHero
                    eyebrow="Admin users"
                    title="User control should feel like control, not cleanup roulette."
                    description="Admin-only control over roles, onboarding data, and contact preferences. This is where the platform stops being a pile of accounts."
                    badges={['Admin only', `${summary.totalUsers} total users`, `${summary.newThisMonth} new this month`]}
                    actions={
                        <>
                            <UserCreateDialog roleOptions={roleOptions} permissionGroups={permissionGroups} channelOptions={channelOptions} />
                            <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                                <Link href="/admin/control-center">Back to control center</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className={adminSecondaryButtonClass}>
                                <Link href="/memberships">Open memberships</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Platform mix</p>
                                <p className="mt-3 text-sm font-semibold tracking-tight text-stone-950">
                                    {summary.owners} owners · {summary.admins} admins · {summary.coaches} coaches · {summary.athletes} athletes
                                </p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Verified email</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{summary.emailVerified}</p>
                                <p className="mt-2 text-sm text-stone-600">Accounts with verified email status kept intact.</p>
                            </div>
                            <div className="rounded-[1.35rem] border border-white/70 bg-white/80 p-4">
                                <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Phone-first users</p>
                                <p className="mt-3 text-2xl font-semibold tracking-tight text-stone-950">{summary.phoneFirst}</p>
                                <p className="mt-2 text-sm text-stone-600">Accounts that prefer phone as the primary contact path.</p>
                            </div>
                        </div>
                    }
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <WorkspaceMetricCard
                        title="Platform users"
                        value={summary.totalUsers.toString()}
                        note={`${summary.owners} owners, ${summary.admins} admins, ${summary.coaches} coaches, and ${summary.athletes} athletes.`}
                        icon={Users}
                    />
                    <WorkspaceMetricCard
                        title="Email verified"
                        value={summary.emailVerified.toString()}
                        note="Accounts with verified email status kept intact."
                        icon={MailCheck}
                    />
                    <WorkspaceMetricCard
                        title="Phone-first users"
                        value={summary.phoneFirst.toString()}
                        note="Accounts that prefer phone as the primary contact path."
                        icon={PhoneCall}
                    />
                    <WorkspaceMetricCard
                        title="New this month"
                        value={summary.newThisMonth.toString()}
                        note="Fresh accounts created since the start of the month."
                        icon={Shield}
                    />
                </section>

                <WorkspacePanel
                    title="Find users fast"
                    description="Filter by search, role, or signup channel before the list gets ugly."
                    contentClassName="p-0"
                >
                    <div className="p-0">
                        <form className="grid gap-4 lg:grid-cols-[1fr_0.26fr_0.26fr_auto]" onSubmit={applyFilters}>
                            <div className="grid gap-2">
                                <Label htmlFor="user-search">Search</Label>
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
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
                                    <Link href={baseRoute} preserveScroll>
                                        Reset
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </div>
                </WorkspacePanel>

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Queue"
                        title="User records with the filters applied."
                        description={`${users.total} record(s) match the current filter set.`}
                    />

                    <WorkspacePanel
                        title="User table"
                        description="Click a name to open the full profile. This table is the tracking view; cards stay out of the way."
                        contentClassName="space-y-4"
                    >
                        {users.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-stone-200/80 p-8 text-center">
                                <p className="font-medium text-stone-950">No users match this filter.</p>
                                <p className="mt-2 text-sm text-stone-500">
                                    Either the filters are tight or the platform is still tiny. Both are fine.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
                                <table className="w-full min-w-[1180px] text-left text-sm">
                                    <thead className="bg-stone-50 text-[0.68rem] font-semibold tracking-[0.18em] text-stone-500 uppercase">
                                        <tr>
                                            <th className="px-4 py-3">User</th>
                                            <th className="px-4 py-3">Roles</th>
                                            <th className="px-4 py-3">Contact</th>
                                            <th className="px-4 py-3">Subscription</th>
                                            <th className="px-4 py-3">Tracking</th>
                                            <th className="px-4 py-3">Created</th>
                                            <th className="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-100">
                                        {users.data.map((user) => {
                                            const ownerLocked = user.roles.includes('owner') && !canManageOwner;

                                            return (
                                            <tr key={user.id} className="align-top transition-colors hover:bg-stone-50/80">
                                                <td className="px-4 py-4">
                                                    <Link
                                                        href={route('admin.users.show', user.id)}
                                                        className="font-semibold text-stone-950 underline-offset-4 hover:text-emerald-700 hover:underline"
                                                    >
                                                        {user.name}
                                                    </Link>
                                                    <p className="mt-1 text-xs text-stone-500">{user.email}</p>
                                                    {user.primaryGoal ? (
                                                        <p className="mt-2 line-clamp-2 max-w-[18rem] text-xs text-stone-600">{user.primaryGoal}</p>
                                                    ) : (
                                                        <p className="mt-2 text-xs text-stone-400">No primary goal set.</p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="flex max-w-[14rem] flex-wrap gap-1.5">
                                                        {user.roles.map((roleName) => (
                                                            <Badge key={roleName} variant="outline">
                                                                {humanizeStatus(roleName)}
                                                            </Badge>
                                                        ))}
                                                        <Badge variant="outline">{humanizeStatus(user.registrationChannel ?? 'email')}</Badge>
                                                    </div>
                                                    <p className="mt-2 text-xs font-medium text-stone-700">{user.position ?? 'No position set'}</p>
                                                    <p className="mt-1 text-xs text-stone-500">{user.permissionCount} permission(s)</p>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <p className="font-medium text-stone-950">
                                                        {humanizeStatus(user.preferredContactMethod ?? 'email')}
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">{user.phone ?? 'No phone'}</p>
                                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                                        <Badge variant={user.emailVerifiedAt ? 'default' : 'outline'}>
                                                            {user.emailVerifiedAt ? 'Email verified' : 'Email pending'}
                                                        </Badge>
                                                        <Badge variant={user.phoneVerifiedAt ? 'default' : 'outline'}>
                                                            {user.phoneVerifiedAt ? 'Phone verified' : 'Phone pending'}
                                                        </Badge>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    {user.currentMembership ? (
                                                        <div className="space-y-2">
                                                            <div className="flex flex-wrap gap-1.5">
                                                                <Badge variant={badgeVariantForMembership(user.currentMembership.status)}>
                                                                    {humanizeStatus(user.currentMembership.status)}
                                                                </Badge>
                                                                <Badge variant="outline">{user.currentMembership.planName}</Badge>
                                                            </div>
                                                            <dl className="grid gap-1 text-xs text-stone-600">
                                                                <div className="flex justify-between gap-4">
                                                                    <dt>Subscribed</dt>
                                                                    <dd className="font-medium text-stone-950">
                                                                        {formatDate(user.currentMembership.startsAt)}
                                                                    </dd>
                                                                </div>
                                                                <div className="flex justify-between gap-4">
                                                                    <dt>Renews</dt>
                                                                    <dd className="font-medium text-stone-950">
                                                                        {formatDate(user.currentMembership.renewsAt)}
                                                                    </dd>
                                                                </div>
                                                                <div className="flex justify-between gap-4">
                                                                    <dt>Ends</dt>
                                                                    <dd className="font-medium text-stone-950">
                                                                        {formatDate(user.currentMembership.endsAt)}
                                                                    </dd>
                                                                </div>
                                                            </dl>
                                                            <p className="text-xs font-medium text-emerald-700">
                                                                {formatDays(user.currentMembership.daysRemaining)}
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        <p className="text-xs text-stone-500">No current membership on record.</p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <dl className="grid gap-1 text-xs text-stone-600">
                                                        <div className="flex justify-between gap-4">
                                                            <dt>Memberships</dt>
                                                            <dd className="font-semibold text-stone-950">{user.membershipsCount}</dd>
                                                        </div>
                                                        <div className="flex justify-between gap-4">
                                                            <dt>Devices</dt>
                                                            <dd className="font-semibold text-stone-950">{user.deviceConnectionsCount}</dd>
                                                        </div>
                                                        <div className="flex justify-between gap-4">
                                                            <dt>Active athletes</dt>
                                                            <dd className="font-semibold text-stone-950">{user.activeAthleteCount}</dd>
                                                        </div>
                                                    </dl>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-stone-700">{formatDate(user.createdAt)}</td>
                                                <td className="px-4 py-4">
                                                    <div className="flex justify-end gap-2">
                                                        <Button asChild variant="outline" size="sm">
                                                            <Link href={route('admin.users.show', user.id)}>Details</Link>
                                                        </Button>
                                                        {ownerLocked ? (
                                                            <Badge variant="outline">Owner locked</Badge>
                                                        ) : (
                                                            <UserEditDialog
                                                                user={user}
                                                                roleOptions={roleOptions}
                                                                permissionGroups={permissionGroups}
                                                                channelOptions={channelOptions}
                                                            />
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </WorkspacePanel>

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

                        <p className="text-muted-foreground text-sm">
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
