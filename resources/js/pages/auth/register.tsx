import { Head, useForm } from '@inertiajs/react';
import { Chrome, LoaderCircle, LockKeyhole, Mail, Phone, ShieldCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';

interface RegisterForm {
    name: string;
    email: string;
    phone: string;
    account_type: 'coach' | 'athlete';
    primary_goal: string;
    preferred_contact_method: 'email' | 'phone';
    password: string;
    password_confirmation: string;
    terms_accepted: boolean;
}

interface SignupMethod {
    value: string;
    label: string;
    headline: string;
    description: string;
    enabled: boolean;
    authorizationUrl: string | null;
}

interface RegisterProps {
    signupMethods: SignupMethod[];
    goalSuggestions: string[];
}

const iconMap = {
    email: Mail,
    google: ShieldCheck,
    apple: ShieldCheck,
    phone: Phone,
} as const;

export default function Register({ signupMethods, goalSuggestions }: RegisterProps) {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        phone: '',
        account_type: 'athlete',
        primary_goal: '',
        preferred_contact_method: 'email',
        password: '',
        password_confirmation: '',
        terms_accepted: false,
    });
    const googleMethod = signupMethods.find((method) => method.value === 'google' && method.enabled && method.authorizationUrl);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const continueWithGoogle = () => {
        if (!googleMethod?.authorizationUrl) {
            return;
        }

        const url = new URL(googleMethod.authorizationUrl, window.location.origin);
        url.searchParams.set('account_type', data.account_type);
        window.location.assign(url.toString());
    };

    return (
        <AuthLayout
            title="Create your Throughline account"
            description={
                googleMethod
                    ? 'Email and Google are live. Apple and phone stay staged until the verification and abuse controls are real.'
                    : 'Start with email now. Google, Apple, and phone auth are staged later once the hardening work is in place.'
            }
        >
            <Head title="Register" />

            <div className="space-y-4">
                <div className="grid gap-3">
                    {signupMethods.map((method) => {
                        const Icon = iconMap[method.value as keyof typeof iconMap] ?? ShieldCheck;

                        return (
                            <div
                                key={method.value}
                                className={cn(
                                    'rounded-2xl border p-4',
                                    method.enabled ? 'border-primary/40 bg-primary/5' : 'border-sidebar-border/70 bg-muted/20',
                                )}
                            >
                                <div className="flex items-start gap-3">
                                    <div
                                        className={cn(
                                            'rounded-full p-2',
                                            method.enabled ? 'bg-primary/10 text-primary' : 'bg-background text-muted-foreground',
                                        )}
                                    >
                                        <Icon className="size-4" />
                                    </div>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">{method.headline}</p>
                                            <span
                                                className={cn(
                                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                                    method.enabled ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                                                )}
                                            >
                                                {method.enabled ? 'Live now' : 'Later stage'}
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground text-sm leading-6">{method.description}</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <form className="flex flex-col gap-6" onSubmit={submit}>
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                disabled={processing}
                                placeholder="Full name"
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                tabIndex={2}
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                disabled={processing}
                                placeholder="email@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone number</Label>
                            <Input
                                id="phone"
                                type="tel"
                                tabIndex={3}
                                autoComplete="tel"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                disabled={processing}
                                placeholder="+966500000000"
                            />
                            <p className="text-muted-foreground text-sm leading-6">
                                Optional for now. It becomes required only if you want phone as the main contact method.
                            </p>
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="account_type">Account type</Label>
                            <Select value={data.account_type} onValueChange={(value: RegisterForm['account_type']) => setData('account_type', value)}>
                                <SelectTrigger id="account_type" tabIndex={4} disabled={processing}>
                                    <SelectValue placeholder="Choose your role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="coach">Coach</SelectItem>
                                    <SelectItem value="athlete">Athlete</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-muted-foreground text-sm leading-6">
                                Coaches get roster and program controls. Athletes get training, membership, and recovery visibility.
                            </p>
                            <InputError message={errors.account_type} />
                        </div>

                        {googleMethod?.authorizationUrl && (
                            <div className="border-primary/20 bg-primary/5 rounded-2xl border p-4">
                                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p className="font-medium">{googleMethod.headline}</p>
                                        <p className="text-muted-foreground text-sm leading-6">
                                            Google sign-up is live. We still need the role first, because silently creating the wrong account type is
                                            stupid.
                                        </p>
                                    </div>
                                    <Button type="button" variant="outline" onClick={continueWithGoogle} disabled={processing}>
                                        <Chrome className="size-4" />
                                        Continue with Google as {data.account_type === 'coach' ? 'Coach' : 'Athlete'}
                                    </Button>
                                </div>
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="primary_goal">Primary goal</Label>
                            <Input
                                id="primary_goal"
                                type="text"
                                tabIndex={5}
                                list="goal-suggestions"
                                value={data.primary_goal}
                                onChange={(e) => setData('primary_goal', e.target.value)}
                                disabled={processing}
                                placeholder="Build strength without losing conditioning"
                            />
                            <datalist id="goal-suggestions">
                                {goalSuggestions.map((goal) => (
                                    <option key={goal} value={goal} />
                                ))}
                            </datalist>
                            <InputError message={errors.primary_goal} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="preferred_contact_method">Preferred contact method</Label>
                            <Select
                                value={data.preferred_contact_method}
                                onValueChange={(value: RegisterForm['preferred_contact_method']) => setData('preferred_contact_method', value)}
                            >
                                <SelectTrigger id="preferred_contact_method" tabIndex={6} disabled={processing}>
                                    <SelectValue placeholder="Choose contact preference" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="email">Email</SelectItem>
                                    <SelectItem value="phone">Phone</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.preferred_contact_method} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={7}
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                disabled={processing}
                                placeholder="Password"
                            />
                            <p className="text-muted-foreground text-sm leading-6">
                                Use something you would not be embarrassed to explain after a breach report.
                            </p>
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                tabIndex={8}
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                disabled={processing}
                                placeholder="Confirm password"
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <div className="border-sidebar-border/70 bg-muted/20 rounded-2xl border p-4">
                            <div className="flex items-start space-x-3">
                                <Checkbox
                                    id="terms_accepted"
                                    checked={data.terms_accepted}
                                    onCheckedChange={(checked) => setData('terms_accepted', Boolean(checked))}
                                    disabled={processing}
                                />
                                <div className="space-y-2">
                                    <Label htmlFor="terms_accepted" className="leading-6">
                                        I understand this MVP stores membership, training, and wearable data, and I agree to the platform terms.
                                    </Label>
                                    <p className="text-muted-foreground text-sm leading-6">This is the boring legal checkbox. It still matters.</p>
                                    <InputError message={errors.terms_accepted} />
                                </div>
                            </div>
                        </div>

                        <Button type="submit" className="mt-2 w-full" tabIndex={9} disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            <LockKeyhole className="h-4 w-4" />
                            Create account
                        </Button>
                    </div>

                    <div className="text-muted-foreground text-center text-sm">
                        Already have an account?{' '}
                        <TextLink href={route('login')} tabIndex={10}>
                            Log in
                        </TextLink>
                    </div>
                </form>
            </div>
        </AuthLayout>
    );
}
