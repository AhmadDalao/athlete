<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MobileAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_keep_signed_in_controls_token_lifetime_and_returns_session_metadata(): void
    {
        Carbon::setTestNow('2026-08-17 12:00:00');
        config([
            'mobile_sessions.standard_lifetime_minutes' => 60,
            'mobile_sessions.remembered_lifetime_minutes' => 1440,
        ]);
        [, $athlete] = $this->athleteInOrganization();

        $standard = $this->postJson('/api/v1/auth/login', [
            'email' => $athlete->email,
            'password' => 'password',
            'device_name' => 'Android standard session',
            'remember_me' => false,
        ])->assertOk()
            ->assertJsonPath('data.session.remembered', false)
            ->assertJsonPath('data.session.device_name', 'Android standard session');

        $remembered = $this->postJson('/api/v1/auth/login', [
            'email' => $athlete->email,
            'password' => 'password',
            'device_name' => 'Android remembered session',
            'remember_me' => true,
        ])->assertOk()
            ->assertJsonPath('data.session.remembered', true)
            ->assertJsonPath('data.session.device_name', 'Android remembered session');

        $this->assertTrue(
            Carbon::parse($standard->json('data.session.expires_at'))->equalTo(now()->addHour()),
        );
        $this->assertTrue(
            Carbon::parse($remembered->json('data.session.expires_at'))->equalTo(now()->addDay()),
        );
    }

    public function test_login_replaces_the_previous_token_for_the_same_device(): void
    {
        [, $athlete] = $this->athleteInOrganization();
        $payload = [
            'email' => $athlete->email,
            'password' => 'password',
            'device_name' => 'Ahmad Android phone',
            'remember_me' => true,
        ];

        $firstToken = $this->postJson('/api/v1/auth/login', $payload)->assertOk()->json('data.token');
        $secondToken = $this->postJson('/api/v1/auth/login', $payload)->assertOk()->json('data.token');

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Ahmad Android phone']);
    }

    public function test_authenticated_mobile_session_reports_its_device_and_expiry(): void
    {
        [$organization, $athlete] = $this->athleteInOrganization();
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $athlete->email,
            'password' => 'password',
            'device_name' => 'Biometric test phone',
            'remember_me' => true,
        ])->assertOk();

        $this->withToken($login->json('data.token'))
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.session.device_name', 'Biometric test phone')
            ->assertJsonPath('data.session.expires_at', $login->json('data.session.expires_at'));
    }

    /** @return array{Organization, User} */
    private function athleteInOrganization(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Mobile Auth Team',
            'slug' => 'mobile-auth-team',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $athlete = User::factory()->create([
            'role' => 'athlete',
            'current_organization_id' => $organization->id,
        ]);
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $athlete->id,
            'role' => 'athlete',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return [$organization, $athlete];
    }
}
