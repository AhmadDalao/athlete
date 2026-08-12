<?php

namespace Tests\Feature;

use App\Livewire\Admin\EmailLogsTable;
use App\Livewire\Admin\Reports;
use App\Models\EmailLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Queries\Admin\OperationsReportQuery;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_filter_and_export_organization_operations_report(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $organization = $this->organizationFor($owner);
        $visibleCoach = User::factory()->create(['role' => 'coach', 'name' => 'Visible Coach', 'email' => 'visible@example.com']);
        $hiddenCoach = User::factory()->create(['role' => 'coach', 'name' => 'Hidden Coach', 'email' => 'hidden@example.com']);
        $this->join($organization, $visibleCoach, 'coach');

        $otherOrganization = Organization::create([
            'name' => 'Other Organization',
            'slug' => 'other-organization',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $this->join($otherOrganization, $hiddenCoach, 'coach', false);

        $context = app(OrganizationContext::class);
        $context->set($organization);
        $this->assertSame(
            ['Visible Coach'],
            OperationsReportQuery::coaches($organization->id, today()->subDays(29)->toDateString(), today()->toDateString())->pluck('name')->all(),
        );
        $context->clear();

        Livewire::actingAs($owner)
            ->test(Reports::class)
            ->set('search', 'missing')
            ->assertSee('No coaches match this report.');

        $response = $this->actingAs($owner)
            ->get(route('admin.reports.export', [
                'from' => today()->subDays(29)->toDateString(),
                'to' => today()->toDateString(),
                'search' => 'Visible',
            ]))
            ->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Visible Coach', $csv);
        $this->assertStringNotContainsString('Hidden Coach', $csv);
    }

    public function test_email_logs_are_a_separate_filterable_exportable_workspace(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $organization = $this->organizationFor($owner);
        EmailLog::create([
            'organization_id' => $organization->id,
            'recipient' => 'sent@example.com',
            'subject' => 'Invitation sent',
            'type' => 'athlete_invite',
            'status' => 'sent',
        ]);
        EmailLog::create([
            'organization_id' => $organization->id,
            'recipient' => 'failed@example.com',
            'subject' => 'Invitation failed',
            'type' => 'athlete_invite',
            'status' => 'failed',
            'error' => 'SMTP rejected message.',
        ]);

        Livewire::actingAs($owner)
            ->test(EmailLogsTable::class)
            ->assertSee('sent@example.com')
            ->assertSee('failed@example.com')
            ->set('status', 'failed')
            ->assertDontSee('sent@example.com')
            ->assertSee('failed@example.com');

        $response = $this->actingAs($owner)
            ->get(route('admin.email-logs.export', ['email_status' => 'failed']))
            ->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('failed@example.com', $csv);
        $this->assertStringNotContainsString('sent@example.com', $csv);
    }

    public function test_organization_admin_cannot_open_platform_logs(): void
    {
        $organizationAdmin = User::factory()->create(['role' => 'athlete']);
        $organization = $this->organizationFor($organizationAdmin, 'organization_admin');

        $this->actingAs($organizationAdmin)
            ->get(route('admin.reports'))
            ->assertOk();

        $this->actingAs($organizationAdmin)
            ->get(route('admin.audit'))
            ->assertForbidden();

        $this->actingAs($organizationAdmin)
            ->get(route('admin.email-logs'))
            ->assertForbidden();

        $this->assertSame($organization->id, $organizationAdmin->fresh()->current_organization_id);
    }

    private function organizationFor(User $user, string $role = 'organization_owner'): Organization
    {
        $organization = Organization::create([
            'owner_id' => $user->id,
            'name' => 'Throughline Test',
            'slug' => 'throughline-test-'.$user->id,
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $this->join($organization, $user, $role);

        return $organization;
    }

    private function join(Organization $organization, User $user, string $role, bool $select = true): void
    {
        OrganizationMembership::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        if ($select) {
            $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
        }
    }
}
