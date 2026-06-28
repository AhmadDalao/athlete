<?php

namespace Tests\Feature;

use App\Enums\DeviceConnectionStatus;
use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\DeviceConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_the_api_access_page(): void
    {
        $user = User::factory()->create([
            'email' => 'athlete-api@example.com',
        ]);
        $user->assignRole(RoleName::Athlete);

        $connection = DeviceConnection::query()->create([
            'user_id' => $user->id,
            'provider' => DeviceProvider::Garmin,
            'status' => DeviceConnectionStatus::Connected,
            'auth_type' => 'ingest_key',
            'external_user_id' => 'garmin-athlete-1',
            'last_synced_at' => now(),
        ]);
        $connection->regenerateIngestKey('thl_test_visible_key_1234567890');
        $connection->save();

        $this->actingAs($user)
            ->get('/api-access')
            ->assertInertia(fn (Assert $page) => $page
                ->component('api-access')
                ->where('viewer.email', 'athlete-api@example.com')
                ->where('abilities.0.name', 'profile:read')
                ->where('managedConnections.0.provider', DeviceProvider::Garmin->value)
                ->where('managedConnections.0.ingest.key', 'thl_test_visible_key_1234567890')
            );
    }

    public function test_user_can_create_a_personal_access_token_from_the_api_access_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Coach);

        $this->actingAs($user)
            ->post(route('api-access.tokens.store', absolute: false), [
                'token_name' => 'coach-script',
                'abilities' => ['profile:read', 'dashboard:read', 'roster:read'],
            ])
            ->assertRedirect(route('api-access.index', absolute: false));

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($user)
            ->get('/api-access')
            ->assertInertia(fn (Assert $page) => $page
                ->component('api-access')
                ->where('generatedToken.tokenName', 'coach-script')
                ->where('generatedToken.abilities.0', 'profile:read')
                ->where('tokens.0.name', 'coach-script')
            );
    }

    public function test_user_can_revoke_their_own_api_access_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Athlete);

        $token = $user->createToken('mobile-app', ['profile:read']);

        $this->actingAs($user)
            ->delete(route('api-access.tokens.destroy', ['tokenId' => $token->accessToken->id], absolute: false))
            ->assertRedirect();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
