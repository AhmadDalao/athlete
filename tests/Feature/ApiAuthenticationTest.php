<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_issue_api_token_and_fetch_me(): void
    {
        $user = User::factory()->create([
            'email' => 'athlete-api@example.test',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole(RoleName::Athlete);

        $tokenResponse = $this->postJson(route('api.v1.auth.tokens.store'), [
            'email' => 'athlete-api@example.test',
            'password' => 'password',
            'token_name' => 'ios-app',
            'abilities' => ['profile:read', 'progress:read'],
        ]);

        $tokenResponse
            ->assertCreated()
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonPath('data.tokenName', 'ios-app')
            ->assertJsonPath('data.abilities.0', 'profile:read')
            ->assertJsonPath('data.user.email', 'athlete-api@example.test');

        $token = $tokenResponse->json('data.token');

        $this->withToken($token)
            ->getJson(route('api.v1.me'))
            ->assertOk()
            ->assertJsonPath('data.viewer.email', 'athlete-api@example.test')
            ->assertJsonPath('data.token.name', 'ios-app');
    }

    public function test_user_cannot_request_disallowed_api_ability(): void
    {
        $user = User::factory()->create([
            'email' => 'athlete-api@example.test',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole(RoleName::Athlete);

        $this->postJson(route('api.v1.auth.tokens.store'), [
            'email' => 'athlete-api@example.test',
            'password' => 'password',
            'token_name' => 'bad-scope',
            'abilities' => ['admin:read'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('abilities');
    }

    public function test_current_personal_access_token_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Athlete);
        $token = $user->createToken('cli', ['profile:read']);

        $this->withToken($token->plainTextToken)
            ->deleteJson(route('api.v1.auth.tokens.current.destroy'))
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
