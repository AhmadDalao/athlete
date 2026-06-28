<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\EmailDeliveryLog;
use App\Models\PlatformAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOperationalLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_open_operational_logs(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole(RoleName::Coach);

        $this->actingAs($coach)
            ->get(route('admin.audit-log.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.email-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_and_export_audit_logs(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Owner']);
        $admin->assignRole(RoleName::Admin);

        PlatformAuditLog::query()->create([
            'actor_user_id' => $admin->id,
            'action' => 'user.updated',
            'entity_type' => 'User',
            'entity_id' => $admin->id,
            'summary' => 'Updated Admin Owner profile.',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-log.index', ['q' => 'Owner']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/audit-log')
                ->where('summary.totalEvents', 1)
                ->where('summary.filteredEvents', 1)
                ->where('logs.data.0.actorName', 'Admin Owner')
                ->where('logs.data.0.action', 'user.updated')
            );

        $response = $this->actingAs($admin)
            ->get(route('admin.audit-log.index', ['export' => 1]));

        $response->assertOk();
        $this->assertStringContainsString('Time,User,Email,Action,Entity', $response->streamedContent());
        $this->assertStringContainsString('user.updated', $response->streamedContent());
    }

    public function test_admin_can_view_and_export_email_logs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        EmailDeliveryLog::query()->create([
            'status' => 'sent',
            'mailer' => 'array',
            'mailable' => 'PasswordReset',
            'recipient' => 'athlete@example.com',
            'subject' => 'Reset your password',
            'source' => 'auth',
            'attempted_at' => now(),
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email-logs.index', ['q' => 'athlete@example.com']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/email-logs')
                ->where('summary.totalAttempts', 1)
                ->where('summary.sent', 1)
                ->where('logs.data.0.recipient', 'athlete@example.com')
                ->where('logs.data.0.status', 'sent')
            );

        $response = $this->actingAs($admin)
            ->get(route('admin.email-logs.index', ['export' => 1]));

        $response->assertOk();
        $this->assertStringContainsString('Time,Status,Type,Recipient,Subject', $response->streamedContent());
        $this->assertStringContainsString('athlete@example.com', $response->streamedContent());
    }

    public function test_laravel_mail_events_create_delivery_log_rows(): void
    {
        Mail::raw('Welcome to Throughline.', function ($message): void {
            $message->to('new-athlete@example.com')->subject('Welcome athlete');
        });

        $this->assertDatabaseHas('email_delivery_logs', [
            'status' => 'sent',
            'recipient' => 'new-athlete@example.com',
            'subject' => 'Welcome athlete',
        ]);
    }
}
