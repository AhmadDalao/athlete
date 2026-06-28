import { AthleteAppShell } from '@/components/athlete-app-shell';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { WorkspacePanel, WorkspaceTable, WorkspaceTableEmpty, WorkspaceTableHeader } from '@/components/workspace-primitives';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Send, UserRound } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface MessageRow {
    id: number;
    senderId: number;
    recipientId: number;
    senderName: string;
    body: string;
    sentAt: string | null;
    readAt: string | null;
    isMine: boolean;
}

interface MessageThread {
    assignmentId: number;
    participant: {
        id: number;
        name: string;
        email: string;
    };
    goal: string | null;
    status: string;
    messages: MessageRow[];
}

interface MessagesProps {
    viewerRole: string | null;
    threads: MessageThread[];
}

interface MessageFormData {
    assignment_id: string;
    body: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Messages',
        href: '/messages',
    },
];

function formatDateTime(value: string | null) {
    if (!value) {
        return 'Not sent';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function MessagesWorkspace({ viewerRole, threads }: MessagesProps) {
    const [selectedId, setSelectedId] = useState<number | null>(threads[0]?.assignmentId ?? null);
    const selectedThread = threads.find((thread) => thread.assignmentId === selectedId) ?? threads[0] ?? null;
    const { data, setData, post, processing, errors, reset } = useForm<MessageFormData>({
        assignment_id: selectedThread ? String(selectedThread.assignmentId) : '',
        body: '',
    });

    const selectThread = (thread: MessageThread) => {
        setSelectedId(thread.assignmentId);
        setData('assignment_id', String(thread.assignmentId));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('messages.store'), {
            preserveScroll: true,
            onSuccess: () => reset('body'),
        });
    };

    return (
        <div className="flex h-full flex-1 flex-col gap-8 bg-white py-8">
            <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-[0_18px_38px_-34px_rgba(15,23,42,0.16)]">
                <Badge variant="outline" className="rounded-full">
                    Messaging V1
                </Badge>
                <h1 className="mt-4 font-['Space_Grotesk'] text-4xl font-bold tracking-[-0.05em] text-stone-950">Coach-athlete messages</h1>
                <p className="mt-3 max-w-3xl text-sm leading-7 text-stone-600">
                    Simple async messages for assigned coach-athlete relationships. No fake live chat, no noise.
                </p>
            </section>

            <div className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                <WorkspacePanel title="Threads" description="Active assignments you can message.">
                    {threads.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-5 text-sm leading-6 text-stone-600">
                            {viewerRole === 'athlete'
                                ? 'No coach is assigned yet, so messaging is unavailable.'
                                : 'No active athlete assignments are ready for messaging.'}
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {threads.map((thread) => (
                                <button
                                    key={thread.assignmentId}
                                    type="button"
                                    onClick={() => selectThread(thread)}
                                    className={cn(
                                        'w-full rounded-2xl border p-4 text-left transition-colors',
                                        selectedThread?.assignmentId === thread.assignmentId
                                            ? 'border-emerald-300 bg-emerald-50'
                                            : 'border-stone-200 bg-white hover:bg-stone-50',
                                    )}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="grid size-11 place-items-center rounded-full bg-stone-100 text-stone-700">
                                            <UserRound className="size-5" />
                                        </div>
                                        <div>
                                            <p className="font-semibold text-stone-950">{thread.participant.name}</p>
                                            <p className="text-sm text-stone-600">{thread.participant.email}</p>
                                            <p className="mt-2 text-xs text-stone-500">{thread.messages.length} message(s)</p>
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}
                </WorkspacePanel>

                <WorkspacePanel
                    title={selectedThread ? selectedThread.participant.name : 'No thread selected'}
                    description={selectedThread?.goal ?? 'Select a thread to read and send messages.'}
                >
                    {selectedThread ? (
                        <div className="space-y-5">
                            <div className="max-h-[32rem] space-y-3 overflow-y-auto rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4">
                                {selectedThread.messages.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-stone-300 bg-white p-5 text-sm text-stone-600">
                                        No messages yet. Start with the one thing that needs action.
                                    </div>
                                ) : (
                                    selectedThread.messages.map((message) => (
                                        <div key={message.id} className={cn('flex', message.isMine ? 'justify-end' : 'justify-start')}>
                                            <div
                                                className={cn(
                                                    'max-w-[82%] rounded-2xl px-4 py-3 text-sm shadow-sm',
                                                    message.isMine ? 'bg-emerald-700 text-white' : 'border border-stone-200 bg-white text-stone-800',
                                                )}
                                            >
                                                <p className="font-semibold">{message.senderName}</p>
                                                <p className="mt-2 leading-6">{message.body}</p>
                                                <p className={cn('mt-2 text-[0.72rem]', message.isMine ? 'text-emerald-100' : 'text-stone-500')}>
                                                    {formatDateTime(message.sentAt)}
                                                    {message.readAt ? ' · Read' : ''}
                                                </p>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <form className="space-y-3" onSubmit={submit}>
                                <input type="hidden" value={data.assignment_id} />
                                <Textarea
                                    value={data.body}
                                    onChange={(event) => setData('body', event.target.value)}
                                    placeholder="Write a direct update, question, or action item."
                                    disabled={processing}
                                />
                                <InputError message={errors.body} />
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing || data.body.trim() === ''}>
                                        Send message
                                        <Send className="size-4" />
                                    </Button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed border-stone-300 bg-stone-50 p-6 text-sm leading-6 text-stone-600">
                            No active messaging thread is available.
                        </div>
                    )}
                </WorkspacePanel>
            </div>

            {threads.length > 0 && (
                <WorkspacePanel title="Message audit table" description="Table-first record of visible messages for tracking.">
                    <WorkspaceTable minWidth="min-w-[820px]">
                        <WorkspaceTableHeader labels={['Thread', 'Sender', 'Message', 'Sent', 'Read state']} />
                        {threads.flatMap((thread) => thread.messages).length === 0 ? (
                            <WorkspaceTableEmpty message="No messages have been sent yet." colSpan={5} />
                        ) : (
                            <tbody>
                                {threads.flatMap((thread) =>
                                    thread.messages.map((message) => (
                                        <tr key={message.id} className="border-t border-stone-100">
                                            <td className="px-5 py-4 font-medium text-stone-950">{thread.participant.name}</td>
                                            <td className="px-5 py-4 text-stone-600">{message.senderName}</td>
                                            <td className="max-w-md px-4 py-3 text-stone-600">{message.body}</td>
                                            <td className="px-5 py-4 text-stone-600">{formatDateTime(message.sentAt)}</td>
                                            <td className="px-5 py-4">
                                                <Badge variant={message.readAt ? 'default' : 'outline'}>{message.readAt ? 'Read' : 'Unread'}</Badge>
                                            </td>
                                        </tr>
                                    )),
                                )}
                            </tbody>
                        )}
                    </WorkspaceTable>
                </WorkspacePanel>
            )}
        </div>
    );
}

export default function MessagesIndex(props: MessagesProps) {
    if (props.viewerRole === 'athlete') {
        return (
            <AthleteAppShell active="messages">
                <Head title="Messages" />
                <div className="mx-auto max-w-6xl px-4 py-6 md:px-6">
                    <MessagesWorkspace {...props} />
                </div>
            </AthleteAppShell>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Messages" />
            <MessagesWorkspace {...props} />
        </AppLayout>
    );
}
