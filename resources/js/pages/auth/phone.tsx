import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Phone, ShieldCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthLayout from '@/layouts/auth-layout';

interface PhoneAuthProps {
    intent: 'login' | 'register';
    status?: string;
    challenge: {
        phoneMasked: string;
        expiresAt: string;
        sentAt: string;
    } | null;
    draft: {
        phone: string;
        email: string;
        name: string;
        accountType: 'coach' | 'athlete';
        primaryGoal: string;
        preferredContactMethod: 'email' | 'phone';
    };
}

interface PhoneChallengeForm {
    intent: 'login' | 'register';
    phone: string;
    email: string;
    name: string;
    account_type: 'coach' | 'athlete';
    primary_goal: string;
    preferred_contact_method: 'email' | 'phone';
    terms_accepted: boolean;
}

interface VerificationForm {
    intent: 'login' | 'register';
    code: string;
}

export default function PhoneAuth({ intent, status, challenge, draft }: PhoneAuthProps) {
    const challengeForm = useForm<PhoneChallengeForm>({
        intent,
        phone: draft.phone ?? '',
        email: draft.email ?? '',
        name: draft.name ?? '',
        account_type: draft.accountType ?? 'athlete',
        primary_goal: draft.primaryGoal ?? '',
        preferred_contact_method: draft.preferredContactMethod ?? 'phone',
        terms_accepted: false,
    });
    const verificationForm = useForm<VerificationForm>({
        intent,
        code: '',
    });

    const requestCode: FormEventHandler = (event) => {
        event.preventDefault();
        challengeForm.post(route('auth.phone.challenges.store'));
    };

    const verifyCode: FormEventHandler = (event) => {
        event.preventDefault();
        verificationForm.post(route('auth.phone.verify'), {
            onFinish: () => verificationForm.reset('code'),
        });
    };

    const isRegister = intent === 'register';

    return (
        <AuthLayout
            title={isRegister ? 'Sign up with phone' : 'Log in with phone'}
            description={
                isRegister
                    ? 'Phone auth uses one-time codes. We still collect the basics so the account lands with the right role instead of random junk.'
                    : 'Enter your phone number, request a one-time code, then finish login without touching a password.'
            }
        >
            <Head title={isRegister ? 'Phone sign up' : 'Phone login'} />

            {status && (
                <div className="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

            {challenge && (
                <div className="border-primary/20 bg-primary/5 space-y-4 rounded-2xl border p-4">
                    <div className="flex items-start gap-3">
                        <div className="bg-primary/10 text-primary rounded-full p-2">
                            <ShieldCheck className="size-4" />
                        </div>
                        <div>
                            <p className="font-medium">Verification code sent</p>
                            <p className="text-muted-foreground text-sm leading-6">
                                We sent a one-time code to {challenge.phoneMasked}. Enter it before{' '}
                                {new Date(challenge.expiresAt).toLocaleTimeString()}.
                            </p>
                        </div>
                    </div>

                    <form className="space-y-4" onSubmit={verifyCode}>
                        <input type="hidden" name="intent" value={verificationForm.data.intent} />
                        <div className="grid gap-2">
                            <Label htmlFor="code">Verification code</Label>
                            <Input
                                id="code"
                                type="text"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                value={verificationForm.data.code}
                                onChange={(event) => verificationForm.setData('code', event.target.value)}
                                placeholder="123456"
                            />
                            <InputError message={verificationForm.errors.code} />
                        </div>
                        <Button type="submit" className="w-full" disabled={verificationForm.processing}>
                            {verificationForm.processing && <LoaderCircle className="size-4 animate-spin" />}
                            Verify code
                        </Button>
                    </form>
                </div>
            )}

            <form className="space-y-5" onSubmit={requestCode}>
                <input type="hidden" name="intent" value={challengeForm.data.intent} />

                {isRegister && (
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            value={challengeForm.data.name}
                            onChange={(event) => challengeForm.setData('name', event.target.value)}
                            placeholder="Full name"
                        />
                        <InputError message={challengeForm.errors.name} />
                    </div>
                )}

                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone number</Label>
                    <Input
                        id="phone"
                        type="tel"
                        autoComplete="tel"
                        value={challengeForm.data.phone}
                        onChange={(event) => challengeForm.setData('phone', event.target.value)}
                        placeholder="+966500000000"
                    />
                    <InputError message={challengeForm.errors.phone} />
                </div>

                {isRegister && (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                autoComplete="email"
                                value={challengeForm.data.email}
                                onChange={(event) => challengeForm.setData('email', event.target.value)}
                                placeholder="email@example.com"
                            />
                            <InputError message={challengeForm.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="account_type">Account type</Label>
                            <Select
                                value={challengeForm.data.account_type}
                                onValueChange={(value: PhoneChallengeForm['account_type']) => challengeForm.setData('account_type', value)}
                            >
                                <SelectTrigger id="account_type">
                                    <SelectValue placeholder="Choose your role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="coach">Coach</SelectItem>
                                    <SelectItem value="athlete">Athlete</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={challengeForm.errors.account_type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="primary_goal">Primary goal</Label>
                            <Input
                                id="primary_goal"
                                type="text"
                                value={challengeForm.data.primary_goal}
                                onChange={(event) => challengeForm.setData('primary_goal', event.target.value)}
                                placeholder="Build strength without losing conditioning"
                            />
                            <InputError message={challengeForm.errors.primary_goal} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="preferred_contact_method">Preferred contact method</Label>
                            <Select
                                value={challengeForm.data.preferred_contact_method}
                                onValueChange={(value: PhoneChallengeForm['preferred_contact_method']) =>
                                    challengeForm.setData('preferred_contact_method', value)
                                }
                            >
                                <SelectTrigger id="preferred_contact_method">
                                    <SelectValue placeholder="Choose contact preference" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="phone">Phone</SelectItem>
                                    <SelectItem value="email">Email</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={challengeForm.errors.preferred_contact_method} />
                        </div>

                        <div className="flex items-start space-x-3 rounded-2xl border p-4">
                            <Checkbox
                                id="terms_accepted"
                                checked={challengeForm.data.terms_accepted}
                                onCheckedChange={(checked) => challengeForm.setData('terms_accepted', checked === true)}
                            />
                            <div className="space-y-1">
                                <Label htmlFor="terms_accepted">I accept the platform terms</Label>
                                <p className="text-muted-foreground text-sm leading-6">
                                    Required for membership billing, training delivery, and coach access.
                                </p>
                                <InputError message={challengeForm.errors.terms_accepted} />
                            </div>
                        </div>
                    </>
                )}

                <Button type="submit" className="w-full" disabled={challengeForm.processing}>
                    {challengeForm.processing && <LoaderCircle className="size-4 animate-spin" />}
                    <Phone className="size-4" />
                    {challenge ? 'Send a new code' : 'Send verification code'}
                </Button>
            </form>

            <div className="text-muted-foreground text-center text-sm">
                {isRegister ? 'Already have an account?' : 'Need the password flow instead?'}{' '}
                <TextLink href={route('login')}>{isRegister ? 'Log in' : 'Use email login'}</TextLink>
            </div>

            {isRegister && (
                <div className="text-muted-foreground text-center text-sm">
                    Prefer the classic flow? <TextLink href={route('register')}>Back to email sign up</TextLink>
                </div>
            )}
        </AuthLayout>
    );
}
