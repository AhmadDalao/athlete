import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    WorkspaceHero,
    WorkspacePanel,
    WorkspaceSectionHeading,
    WorkspaceTable,
    WorkspaceTableEmpty,
    WorkspaceTableHeader,
    WorkspaceTablePageSize,
} from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCheck, Send } from 'lucide-react';
import { type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: '/notifications',
    },
];

interface Option {
    value: string;
    label: string;
}

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface NotificationRow {
    id: number;
    title: string;
    body: string;
    actionLabel: string | null;
    actionUrl: string | null;
    targetType: string;
    targetRole: string | null;
    startsAt: string | null;
    expiresAt: string | null;
    createdAt: string | null;
    creatorName: string | null;
    readAt: string | null;
}

interface NotificationPaginator {
    data: NotificationRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
    per_page?: number | string;
}

interface NotificationsProps {
    notifications: NotificationPaginator;
    canCreateNotifications: boolean;
    roleOptions: Option[];
    userOptions: UserOption[];
}

interface NotificationCreateFormData {
    title: string;
    body: string;
    target_type: string;
    target_role: string;
    target_user_id: string;
    action_label: string;
    action_url: string;
    starts_at: string;
    expires_at: string;
}

function humanizeStatus(status: string) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function NotificationAction({ notification }: { notification: NotificationRow }) {
    if (!notification.actionUrl) {
        return null;
    }

    const label = notification.actionLabel || 'Open';
    const external = /^https?:\/\//i.test(notification.actionUrl);

    if (external) {
        return (
            <Button asChild size="sm" variant="outline">
                <a href={notification.actionUrl} target="_blank" rel="noreferrer">
                    {label}
                </a>
            </Button>
        );
    }

    return (
        <Button asChild size="sm" variant="outline">
            <Link href={notification.actionUrl}>{label}</Link>
        </Button>
    );
}

function NotificationComposer({ roleOptions, userOptions }: { roleOptions: Option[]; userOptions: UserOption[] }) {
    const { data, setData, post, processing, errors, reset } = useForm<NotificationCreateFormData>({
        title: '',
        body: '',
        target_type: 'all',
        target_role: 'coach',
        target_user_id: userOptions[0] ? String(userOptions[0].id) : '',
        action_label: '',
        action_url: '',
        starts_at: '',
        expires_at: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('notifications.store'), {
            preserveScroll: true,
            onSuccess: () => reset('title', 'body', 'action_label', 'action_url', 'starts_at', 'expires_at'),
        });
    };

    return (
        <WorkspacePanel
            title="Publish notification"
            description="Send a system message to everyone, a role, or one specific user."
            contentClassName="p-0"
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="notification-title">Title</Label>
                        <Input id="notification-title" value={data.title} onChange={(event) => setData('title', event.target.value)} />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notification-target">Target</Label>
                        <Select value={data.target_type} onValueChange={(value) => setData('target_type', value)}>
                            <SelectTrigger id="notification-target">
                                <SelectValue placeholder="Target" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Everyone</SelectItem>
                                <SelectItem value="role">Role</SelectItem>
                                <SelectItem value="user">Specific user</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.target_type} />
                    </div>

                    {data.target_type === 'role' && (
                        <div className="grid gap-2">
                            <Label htmlFor="notification-role">Role</Label>
                            <Select value={data.target_role} onValueChange={(value) => setData('target_role', value)}>
                                <SelectTrigger id="notification-role">
                                    <SelectValue placeholder="Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roleOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.target_role} />
                        </div>
                    )}

                    {data.target_type === 'user' && (
                        <div className="grid gap-2">
                            <Label htmlFor="notification-user">User</Label>
                            <Select value={data.target_user_id} onValueChange={(value) => setData('target_user_id', value)}>
                                <SelectTrigger id="notification-user">
                                    <SelectValue placeholder="User" />
                                </SelectTrigger>
                                <SelectContent>
                                    {userOptions.map((user) => (
                                        <SelectItem key={user.id} value={String(user.id)}>
                                            {user.name} · {user.email}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.target_user_id} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="notification-action-label">Action label</Label>
                        <Input
                            id="notification-action-label"
                            value={data.action_label}
                            onChange={(event) => setData('action_label', event.target.value)}
                            placeholder="Open training"
                        />
                        <InputError message={errors.action_label} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notification-action-url">Action URL</Label>
                        <Input
                            id="notification-action-url"
                            value={data.action_url}
                            onChange={(event) => setData('action_url', event.target.value)}
                            placeholder="/training"
                        />
                        <InputError message={errors.action_url} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notification-starts">Starts at</Label>
                        <Input
                            id="notification-starts"
                            type="datetime-local"
                            value={data.starts_at}
                            onChange={(event) => setData('starts_at', event.target.value)}
                        />
                        <InputError message={errors.starts_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notification-expires">Expires at</Label>
                        <Input
                            id="notification-expires"
                            type="datetime-local"
                            value={data.expires_at}
                            onChange={(event) => setData('expires_at', event.target.value)}
                        />
                        <InputError message={errors.expires_at} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="notification-body">Message</Label>
                    <Textarea id="notification-body" value={data.body} onChange={(event) => setData('body', event.target.value)} rows={5} />
                    <InputError message={errors.body} />
                </div>

                <Button type="submit" disabled={processing} className="rounded-full bg-stone-950 text-white hover:bg-stone-800">
                    <Send className="size-4" />
                    Publish
                </Button>
            </form>
        </WorkspacePanel>
    );
}

export default function NotificationsIndex({ notifications, canCreateNotifications, roleOptions, userOptions }: NotificationsProps) {
    const unreadCount = notifications.data.filter((notification) => !notification.readAt).length;
    const perPage = String(notifications.per_page ?? '10');
    const updatePerPage = (value: string) => {
        router.get(route('notifications.index'), { per_page: value }, { only: ['notifications'], preserveScroll: true, preserveState: true, replace: true });
    };

    const markRead = (id: number) => {
        router.post(route('notifications.read', id), {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />

            <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
                <WorkspaceHero
                    eyebrow="Notification center"
                    title="System messages should be visible, targeted, and easy to clear."
                    description="Admins can broadcast product updates or targeted account notices. Users get a simple read state instead of hunting through email."
                    badges={[
                        `${notifications.total} visible`,
                        `${unreadCount} unread on this page`,
                        canCreateNotifications ? 'Admin composer enabled' : 'Read only',
                    ]}
                    actions={
                        <>
                            <Button
                                type="button"
                                size="lg"
                                className="rounded-full bg-stone-950 text-white hover:bg-stone-800"
                                onClick={() => router.post(route('notifications.read-all'), {}, { preserveScroll: true })}
                            >
                                <CheckCheck className="size-4" />
                                Mark all read
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full border-stone-300 bg-white text-stone-900">
                                <Link href={route('dashboard')}>Back to dashboard</Link>
                            </Button>
                        </>
                    }
                    aside={
                        <div className="rounded-[1.35rem] border border-white/70 bg-white/82 p-4">
                            <p className="text-[0.68rem] font-semibold tracking-[0.22em] text-stone-500 uppercase">Unread here</p>
                            <p className="mt-3 text-3xl font-semibold tracking-tight text-stone-950">{unreadCount}</p>
                            <p className="mt-2 text-sm leading-6 text-stone-600">
                                The header badge uses the full unread count across visible notifications.
                            </p>
                        </div>
                    }
                />

                {canCreateNotifications && (
                    <section className="space-y-4">
                        <WorkspaceSectionHeading
                            eyebrow="Admin broadcast"
                            title="Create a notification."
                            description="Use this for release notes, billing reminders, support notices, or athlete-specific follow-up."
                        />
                        <NotificationComposer roleOptions={roleOptions} userOptions={userOptions} />
                    </section>
                )}

                <section className="space-y-4">
                    <WorkspaceSectionHeading
                        eyebrow="Inbox"
                        title="Visible system notifications."
                        description="Read state is per user. A coach clearing a notification does not clear it for the athlete."
                    />
                    <WorkspacePanel
                        title="Notification list"
                        description={`${notifications.total} notification(s) visible.`}
                        contentClassName="space-y-3"
                    >
                        <div className="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-stone-50/40 p-4 lg:flex-row lg:items-center lg:justify-between">
                            <WorkspaceTablePageSize value={perPage} onChange={updatePerPage} />
                            <p className="text-sm text-stone-500">Showing {notifications.data.length} of {notifications.total} matching notifications.</p>
                        </div>
                        <WorkspaceTable minWidth="min-w-[980px]">
                            <WorkspaceTableHeader labels={['Notification', 'Target', 'Published', 'Creator', 'Read state', 'Active window', 'Actions']} />
                            {notifications.data.length === 0 ? (
                                <WorkspaceTableEmpty message="No notifications are visible right now." colSpan={7} />
                            ) : (
                                <tbody className="divide-y divide-stone-100">
                                    {notifications.data.map((notification) => (
                                        <tr key={notification.id} className="align-top transition-colors hover:bg-stone-50/80">
                                            <td className="px-5 py-4">
                                                <p className="font-semibold text-stone-950">{notification.title}</p>
                                                <p className="mt-1 line-clamp-3 max-w-[24rem] text-sm leading-6 text-stone-600">
                                                    {notification.body}
                                                </p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <Badge variant="outline">
                                                    {notification.targetType === 'role'
                                                        ? `Role: ${humanizeStatus(notification.targetRole ?? '')}`
                                                        : humanizeStatus(notification.targetType)}
                                                </Badge>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-700">{notification.createdAt ?? 'Unknown'}</td>
                                            <td className="px-5 py-4 text-sm text-stone-700">{notification.creatorName ?? 'System'}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={notification.readAt ? 'outline' : 'default'}>
                                                    {notification.readAt ? `Read ${notification.readAt}` : 'Unread'}
                                                </Badge>
                                            </td>
                                            <td className="px-5 py-4 text-sm text-stone-700">
                                                {notification.startsAt ?? 'Now'} → {notification.expiresAt ?? 'No expiry'}
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex flex-col gap-2">
                                                    <NotificationAction notification={notification} />
                                                    {!notification.readAt && (
                                                        <Button type="button" size="sm" onClick={() => markRead(notification.id)}>
                                                            Mark read
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            )}
                        </WorkspaceTable>
                    </WorkspacePanel>

                    <div className="flex items-center justify-between">
                        <Button variant="outline" asChild disabled={!notifications.prev_page_url}>
                            {notifications.prev_page_url ? (
                                <Link href={notifications.prev_page_url} preserveScroll>
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

                        <p className="text-sm text-stone-500">
                            Page {notifications.current_page} of {notifications.last_page}
                        </p>

                        <Button variant="outline" asChild disabled={!notifications.next_page_url}>
                            {notifications.next_page_url ? (
                                <Link href={notifications.next_page_url} preserveScroll>
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
