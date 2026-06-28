import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import MarketingLayout from '@/layouts/marketing-layout';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Mail, MessageSquareText, PhoneCall } from 'lucide-react';
import type { FormEvent } from 'react';

type RoleInterest = '' | 'coach' | 'athlete' | 'admin' | 'other';

interface ContactFormData {
    name: string;
    email: string;
    phone: string;
    organization: string;
    role_interest: RoleInterest;
    message: string;
}

interface ContactPageProps {
    prefill?: {
        requestedCoach?: string | null;
        message?: string | null;
    };
}

const roleOptions: Array<{ value: RoleInterest; label: string }> = [
    { value: '', label: 'Choose one' },
    { value: 'coach', label: 'Coach' },
    { value: 'athlete', label: 'Athlete' },
    { value: 'admin', label: 'Admin or operator' },
    { value: 'other', label: 'Other' },
];

export default function Contact() {
    const { auth, flash, prefill } = usePage<SharedData & ContactPageProps>().props;
    const { data, setData, post, processing, errors } = useForm<ContactFormData>({
        name: auth.user?.name ?? '',
        email: auth.user?.email ?? '',
        phone: auth.user?.phone ?? '',
        organization: '',
        role_interest:
            auth.user?.primary_role === 'coach' || auth.user?.primary_role === 'athlete' || auth.user?.primary_role === 'admin'
                ? (auth.user.primary_role as RoleInterest)
                : '',
        message: prefill?.message ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('contact.store'), {
            preserveScroll: true,
        });
    };

    return (
        <MarketingLayout
            title="Contact Us"
            description="Send Throughline a real contact request. The form is stored in the backend and ready for follow-up."
        >
            <Head title="Contact Us" />

            <section className="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
                <div className="space-y-6">
                    <div className="rounded-[2rem] border border-white/70 bg-[linear-gradient(135deg,rgba(255,247,237,0.94),rgba(255,255,255,0.96)_44%,rgba(236,253,245,0.94))] p-7 text-stone-950 shadow-[0_28px_64px_rgba(55,41,19,0.12)]">
                        <p className="text-xs font-semibold tracking-[0.24em] text-stone-500 uppercase">Contact us</p>
                        <h1 className="mt-3 font-['Space_Grotesk'] text-4xl font-bold tracking-tight">
                            Send the real problem, not a vague “let’s connect.”
                        </h1>
                        <p className="mt-4 text-sm leading-7 text-stone-700">
                            Tell us what you are building, who needs access, what should integrate, and where the current workflow is breaking down.
                        </p>
                        {prefill?.requestedCoach ? (
                            <p className="mt-4 rounded-2xl border border-stone-900/10 bg-white/72 px-4 py-3 text-sm leading-6 text-stone-700">
                                Coach request: <span className="font-semibold text-stone-950">{prefill.requestedCoach}</span>
                            </p>
                        ) : null}
                    </div>

                    <div className="rounded-[1.75rem] border border-stone-900/10 bg-white/75 p-6 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur">
                        <h2 className="font-['Space_Grotesk'] text-2xl font-bold tracking-tight text-stone-950">Use this page for</h2>
                        <ul className="mt-5 space-y-4 text-sm leading-7 text-stone-700">
                            <li className="flex gap-3">
                                <MessageSquareText className="mt-1 h-5 w-5 shrink-0 text-amber-800" />
                                Product questions, implementation help, or custom workflow requests.
                            </li>
                            <li className="flex gap-3">
                                <PhoneCall className="mt-1 h-5 w-5 shrink-0 text-amber-800" />
                                Coaching businesses that need roster, device, and membership flows cleaned up.
                            </li>
                            <li className="flex gap-3">
                                <Mail className="mt-1 h-5 w-5 shrink-0 text-amber-800" />
                                Partnerships or service integrations that need API or ingest planning.
                            </li>
                        </ul>
                    </div>

                    <div className="rounded-[1.75rem] border border-stone-900/10 bg-white/75 p-6 shadow-[0_18px_40px_rgba(61,47,27,0.08)] backdrop-blur">
                        <p className="text-xs font-semibold tracking-[0.22em] text-stone-500 uppercase">What happens next</p>
                        <p className="mt-3 text-sm leading-7 text-stone-700">
                            The form is stored in the backend as a contact submission with timestamp, source, IP, and user context when available.
                            That means the message does not disappear into the void.
                        </p>
                        {!auth.user ? (
                            <p className="mt-4 text-sm leading-7 text-stone-700">
                                If you already have an account, you can also{' '}
                                <Link href={route('login')} className="font-semibold text-stone-950 underline underline-offset-4">
                                    log in
                                </Link>{' '}
                                and keep the same conversation tied to your user record.
                            </p>
                        ) : null}
                    </div>
                </div>

                <div className="rounded-[2rem] border border-stone-900/10 bg-white/80 p-7 shadow-[0_22px_48px_rgba(61,47,27,0.08)] backdrop-blur lg:p-8">
                    {flash?.success ? (
                        <Alert className="mb-6 border-emerald-200 bg-emerald-50 text-emerald-950">
                            <CheckCircle2 className="h-4 w-4" />
                            <AlertTitle>Message received</AlertTitle>
                            <AlertDescription>{flash.success}</AlertDescription>
                        </Alert>
                    ) : null}

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    placeholder="Your name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    placeholder="you@company.com"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(event) => setData('phone', event.target.value)}
                                    placeholder="+1 555 123 4567"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="organization">Organization</Label>
                                <Input
                                    id="organization"
                                    value={data.organization}
                                    onChange={(event) => setData('organization', event.target.value)}
                                    placeholder="Team, gym, or company"
                                />
                                <InputError message={errors.organization} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="role_interest">You are contacting us as</Label>
                            <select
                                id="role_interest"
                                value={data.role_interest}
                                onChange={(event) => setData('role_interest', event.target.value as RoleInterest)}
                                className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                            >
                                {roleOptions.map((option) => (
                                    <option key={option.value || 'blank'} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.role_interest} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="message">Message</Label>
                            <Textarea
                                id="message"
                                value={data.message}
                                onChange={(event) => setData('message', event.target.value)}
                                placeholder="Tell us what you need, what should integrate, and where the current workflow is broken."
                                className="min-h-40"
                            />
                            <InputError message={errors.message} />
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm leading-6 text-stone-600">
                                By sending this, you are asking for a reply. Fair warning: vague messages get vague progress.
                            </p>
                            <Button type="submit" disabled={processing} className="rounded-full bg-stone-950 px-7 text-stone-50 hover:bg-stone-800">
                                {processing ? 'Sending...' : 'Send message'}
                            </Button>
                        </div>
                    </form>
                </div>
            </section>
        </MarketingLayout>
    );
}
