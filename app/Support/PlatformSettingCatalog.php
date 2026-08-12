<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class PlatformSettingCatalog
{
    public const TOGGLES = [
        'public_features_enabled',
        'public_pricing_enabled',
        'public_contact_enabled',
        'request_access_enabled',
        'invitations_enabled',
    ];

    public static function defaults(): array
    {
        return [
            'app_name' => 'Throughline',
            'tagline' => 'Coaching, connected.',
            'logo_path' => '',
            'default_theme' => 'system',
            'support_email' => 'support@throughline.test',
            'support_phone' => '',
            'contact_address' => '',
            'footer_copy' => 'One clear system for coaching, execution, and measurable progress.',
            'terms_url' => '',
            'privacy_url' => '',
            'homepage_eyebrow' => 'Coaching, connected',
            'homepage_headline' => 'Build the plan.',
            'homepage_headline_accent' => 'Prove the progress.',
            'homepage_subheadline' => 'Throughline gives coaches one clean place to program training, guide athletes, and see what actually happened.',
            'homepage_primary_label' => 'Request access',
            'homepage_secondary_label' => 'See the platform',
            'features_headline' => 'Less admin. Better coaching decisions.',
            'features_subheadline' => 'The core workflow stays direct from invitation to completed session.',
            'coach_headline' => 'Run the roster without losing the athlete.',
            'coach_description' => 'Build reusable programs, assign schedules, review adherence, and respond with context.',
            'athlete_headline' => 'Open the app. Know exactly what to do.',
            'athlete_description' => 'Today’s work, coaching media, set targets, timers, notes, and progress in one focused flow.',
            'contact_headline' => 'Tell us what you need.',
            'contact_subheadline' => 'Questions, coaching setup, or platform access.',
            'contact_button_label' => 'Send message',
            'pricing_headline' => 'Start with the coaching workflow you need.',
            'pricing_subheadline' => 'Plans for athletes, coaches, and teams.',
            'plan_one_name' => 'Athlete',
            'plan_one_price' => 'Contact us',
            'plan_one_description' => 'Follow training and record progress.',
            'plan_one_features' => "Workout calendar\nProgress log",
            'plan_two_name' => 'Coach',
            'plan_two_price' => 'Contact us',
            'plan_two_description' => 'Run a private roster.',
            'plan_two_features' => "Athlete invites\nProgram builder",
            'plan_three_name' => 'Team',
            'plan_three_price' => 'Custom',
            'plan_three_description' => 'Operate a whole training group.',
            'plan_three_features' => "Admin controls\nExports",
            'public_features_enabled' => true,
            'public_pricing_enabled' => true,
            'public_contact_enabled' => true,
            'request_access_enabled' => true,
            'invitations_enabled' => true,
            'invite_expiry_days' => '7',
            'invite_email_subject' => 'Your Throughline athlete invitation',
            'invite_email_body' => "Coach {coach_name} invited you to {app_name}.\n\nAccept here: {invite_link}\n\nThis invite expires on {expires_at}.",
            'mail_from_name' => 'Throughline',
            'mail_from_address' => '',
        ];
    }

    public static function rules(): array
    {
        $rules = [
            'app_name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'default_theme' => ['required', Rule::in(['system', 'dark', 'light'])],
            'support_email' => ['required', 'email', 'max:160'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'contact_address' => ['nullable', 'string', 'max:240'],
            'footer_copy' => ['required', 'string', 'max:240'],
            'terms_url' => ['nullable', 'url:http,https', 'max:500'],
            'privacy_url' => ['nullable', 'url:http,https', 'max:500'],
            'homepage_eyebrow' => ['required', 'string', 'max:80'],
            'homepage_headline' => ['required', 'string', 'max:120'],
            'homepage_headline_accent' => ['nullable', 'string', 'max:120'],
            'homepage_subheadline' => ['nullable', 'string', 'max:320'],
            'homepage_primary_label' => ['required', 'string', 'max:60'],
            'homepage_secondary_label' => ['required', 'string', 'max:60'],
            'features_headline' => ['required', 'string', 'max:160'],
            'features_subheadline' => ['nullable', 'string', 'max:260'],
            'coach_headline' => ['required', 'string', 'max:160'],
            'coach_description' => ['nullable', 'string', 'max:260'],
            'athlete_headline' => ['required', 'string', 'max:160'],
            'athlete_description' => ['nullable', 'string', 'max:260'],
            'contact_headline' => ['required', 'string', 'max:160'],
            'contact_subheadline' => ['nullable', 'string', 'max:260'],
            'contact_button_label' => ['required', 'string', 'max:80'],
            'pricing_headline' => ['required', 'string', 'max:160'],
            'pricing_subheadline' => ['nullable', 'string', 'max:260'],
            'invite_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'invite_email_subject' => ['required', 'string', 'max:180'],
            'invite_email_body' => ['required', 'string', 'max:2000'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
            'mail_from_address' => ['nullable', 'email', 'max:160'],
        ];

        foreach (['one', 'two', 'three'] as $plan) {
            $rules["plan_{$plan}_name"] = ['required', 'string', 'max:80'];
            $rules["plan_{$plan}_price"] = ['required', 'string', 'max:80'];
            $rules["plan_{$plan}_description"] = ['nullable', 'string', 'max:180'];
            $rules["plan_{$plan}_features"] = ['nullable', 'string', 'max:600'];
        }

        foreach (self::TOGGLES as $toggle) {
            $rules[$toggle] = ['boolean'];
        }

        return collect($rules)->mapWithKeys(fn (array $rule, string $key): array => ["settings.{$key}" => $rule])->all();
    }

    public static function groupFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'mail_') => 'mail',
            str_starts_with($key, 'invite_'), $key === 'invitations_enabled' => 'invitations',
            str_starts_with($key, 'contact_'), str_starts_with($key, 'support_') => 'contact',
            str_starts_with($key, 'pricing_'), str_starts_with($key, 'plan_') => 'pricing',
            str_starts_with($key, 'public_'), str_starts_with($key, 'request_') => 'visibility',
            str_starts_with($key, 'homepage_'), str_starts_with($key, 'features_'),
            str_starts_with($key, 'coach_'), str_starts_with($key, 'athlete_') => 'content',
            default => 'branding',
        };
    }

    public static function normalizeForForm(array $values): array
    {
        foreach (self::TOGGLES as $toggle) {
            $values[$toggle] = filter_var($values[$toggle] ?? false, FILTER_VALIDATE_BOOL);
        }

        return $values;
    }

    public static function serialize(string $key, mixed $value): string
    {
        return in_array($key, self::TOGGLES, true) ? ($value ? '1' : '0') : (string) ($value ?? '');
    }
}
