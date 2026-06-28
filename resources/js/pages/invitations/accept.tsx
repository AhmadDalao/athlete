import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarClock, Dumbbell, LoaderCircle, LockKeyhole, MailCheck, UserRoundCheck } from 'lucide-react';
import { type FormEvent } from 'react';

interface InvitationAcceptProps {
    token: string;
    status: string;
    existingUser: boolean;
    invitation: {
        name: string;
        email: string;
        phone: string | null;
        goal: string | null;
        expiresAt: string | null;
        coach: {
            name: string | null;
            email: string | null;
        };
    };
}

interface AcceptFormData {
    name: string;
    phone: string;
    primary_goal: string;
    preferred_contact_method: 'email' | 'phone';
    password: string;
    password_confirmation: string;
    terms_accepted: boolean;
}

function humanize(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

export default function InvitationAccept({ token, status, existingUser, invitation }: InvitationAcceptProps) {
    const { data, setData, post, processing, errors, reset } = useForm<AcceptFormData>({
        name: invitation.name ?? '',
        phone: invitation.phone ?? '',
        primary_goal: invitation.goal ?? '',
        preferred_contact_method: 'email',
        password: '',
        password_confirmation: '',
        terms_accepted: false,
    });
    const closed = status !== 'pending';

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('invitations.accept.store', token), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={closed ? `Invitation ${humanize(status)}` : 'Accept your coach invitation'}
            description={
                closed
                    ? 'This invitation cannot be used anymore. Ask your coach to resend it from the roster invitation table.'
                    : `${invitation.coach.name ?? 'Your coach'} invited you to join their Throughline roster.`
            }
        >
            <Head title="Accept invitation" />
            <div className="space-y-5">
                <div className="rounded-[1.5rem] border border-stone-200 bg-stone-50 p-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">Coach</p>
                            <p className="mt-2 font-semibold text-stone-950">{invitation.coach.name ?? 'Coach'}</p>
                            <p className="mt-1 text-sm text-stone-500">{invitation.coach.email}</p>
                        </div>
                        <div>
                            <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">Athlete email</p>
                            <p className="mt-2 font-semibold text-stone-950">{invitation.email}</p>
                            <p className="mt-1 text-sm text-stone-500">{existingUser ? 'Existing athlete account' : 'New athlete account'}</p>
                        </div>
                    </div>
                    <div className="mt-5 grid gap-3">
                        <div className="flex items-start gap-3 rounded-2xl bg-white p-4">
                            <Dumbbell className="mt-1 size-4 text-teal-700" />
                            <p className="text-sm leading-6 text-stone-600">{invitation.goal ?? 'Your coach will assign your first training goal after setup.'}</p>
                        </div>
                        <div className="flex items-start gap-3 rounded-2xl bg-white p-4">
                            <CalendarClock className="mt-1 size-4 text-teal-700" />
                            <p className="text-sm leading-6 text-stone-600">Expires {invitation.expiresAt ?? 'soon'}.</p>
                        </div>
                    </div>
                </div>

                {closed ? (
                    <Button asChild className="w-full">
                        <Link href={route('login')}>Go to login</Link>
                    </Button>
                ) : (
                    <form className="space-y-5" onSubmit={submit}>
                        <InputError message={errors.invitation} />
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} disabled={processing} />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input id="phone" value={data.phone} onChange={(event) => setData('phone', event.target.value)} disabled={processing} />
                            <InputError message={errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="goal">Primary goal</Label>
                            <Textarea
                                id="goal"
                                value={data.primary_goal}
                                onChange={(event) => setData('primary_goal', event.target.value)}
                                disabled={processing}
                                rows={3}
                            />
                            <InputError message={errors.primary_goal} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="contact">Preferred contact</Label>
                            <Select
                                value={data.preferred_contact_method}
                                onValueChange={(value: AcceptFormData['preferred_contact_method']) => setData('preferred_contact_method', value)}
                            >
                                <SelectTrigger id="contact" disabled={processing}>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="email">Email</SelectItem>
                                    <SelectItem value="phone">Phone</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.preferred_contact_method} />
                        </div>
                        <div className="rounded-2xl border border-stone-200 bg-white p-4">
                            <div className="mb-4 flex items-start gap-3">
                                {existingUser ? <LockKeyhole className="mt-1 size-4 text-stone-700" /> : <UserRoundCheck className="mt-1 size-4 text-stone-700" />}
                                <p className="text-sm leading-6 text-stone-600">
                                    {existingUser
                                        ? 'This email already exists. Enter the existing account password to attach this coach safely.'
                                        : 'Create your athlete password. The invite email is treated as verified.'}
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password-confirmation">Confirm password</Label>
                                    <Input
                                        id="password-confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(event) => setData('password_confirmation', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>
                            </div>
                        </div>
                        <label className="flex items-start gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-sm leading-6 text-stone-600">
                            <Checkbox checked={data.terms_accepted} onCheckedChange={(value) => setData('terms_accepted', value === true)} disabled={processing} />
                            <span>I accept the platform terms and understand my coach can see training, progress, wearable, membership, and file records tied to this coaching relationship.</span>
                        </label>
                        <InputError message={errors.terms_accepted} />
                        <Button type="submit" disabled={processing} className="w-full bg-teal-700 text-white hover:bg-teal-800">
                            {processing ? <LoaderCircle className="size-4 animate-spin" /> : <MailCheck className="size-4" />}
                            Accept invitation
                        </Button>
                    </form>
                )}
            </div>
        </AuthLayout>
    );
}
