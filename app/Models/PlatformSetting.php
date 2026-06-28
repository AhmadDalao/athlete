<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'help',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, array{group:string,label:string,help:string,default:string}>
     */
    public static function definitions(): array
    {
        return [
            'platform_name' => [
                'group' => 'General',
                'label' => 'Platform name',
                'help' => 'Displayed in browser titles, documentation, and operator screens.',
                'default' => 'Throughline',
            ],
            'support_email' => [
                'group' => 'General',
                'label' => 'Support email',
                'help' => 'Public support destination shown to users and admins.',
                'default' => 'support@athlete.ahmaddalao.com',
            ],
            'mail_from_name' => [
                'group' => 'Mail',
                'label' => 'Mail from name',
                'help' => 'Human-readable sender name. SMTP credentials still live in .env.',
                'default' => (string) config('mail.from.name', 'Throughline'),
            ],
            'mail_from_address' => [
                'group' => 'Mail',
                'label' => 'Mail from address',
                'help' => 'Default sender address shown in email settings.',
                'default' => (string) config('mail.from.address', 'hello@example.com'),
            ],
            'mail_reply_to' => [
                'group' => 'Mail',
                'label' => 'Reply-to email',
                'help' => 'Where replies should go when support automations are added.',
                'default' => 'support@athlete.ahmaddalao.com',
            ],
            'home_eyebrow' => [
                'group' => 'Public text',
                'label' => 'Home eyebrow',
                'help' => 'Small label above the public homepage headline.',
                'default' => 'Coach the whole athlete',
            ],
            'home_headline' => [
                'group' => 'Public text',
                'label' => 'Home headline',
                'help' => 'Main public homepage headline.',
                'default' => 'Stop running coaching, recovery, payments, and progress from five disconnected tools.',
            ],
            'home_subheadline' => [
                'group' => 'Public text',
                'label' => 'Home subheadline',
                'help' => 'Paragraph below the homepage headline.',
                'default' => 'Throughline is the operating system for subscription-based coaching. Coaches manage rosters, training programs, athlete check-ins, memberships, and device-backed metrics in one place instead of duct-taping apps together.',
            ],
            'dashboard_admin_note' => [
                'group' => 'Dashboard text',
                'label' => 'Admin dashboard note',
                'help' => 'Admin-facing reminder shown in system settings and future dashboard copy.',
                'default' => 'Keep renewals, failed payments, device issues, and user onboarding visible.',
            ],
            'dashboard_coach_note' => [
                'group' => 'Dashboard text',
                'label' => 'Coach dashboard note',
                'help' => 'Coach-facing reminder shown in system settings and future dashboard copy.',
                'default' => 'Know who needs attention, what is scheduled, and what is missing.',
            ],
            'dashboard_athlete_note' => [
                'group' => 'Dashboard text',
                'label' => 'Athlete dashboard note',
                'help' => 'Athlete-facing reminder shown in system settings and future dashboard copy.',
                'default' => 'Follow the plan, log the session, and keep the recovery picture honest.',
            ],
            'maintenance_notice' => [
                'group' => 'System control',
                'label' => 'Maintenance notice',
                'help' => 'Optional operator note for planned downtime or system warnings.',
                'default' => '',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function values(): array
    {
        $definitions = self::definitions();
        try {
            $stored = self::query()
                ->whereIn('key', array_keys($definitions))
                ->pluck('value', 'key');
        } catch (Throwable) {
            $stored = collect();
        }

        return collect($definitions)
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => (string) ($stored[$key] ?? $definition['default']),
            ])
            ->all();
    }

    public static function valueFor(string $key): string
    {
        $definitions = self::definitions();
        $default = $definitions[$key]['default'] ?? '';

        try {
            return (string) (self::query()->where('key', $key)->value('value') ?? $default);
        } catch (Throwable) {
            return (string) $default;
        }
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
