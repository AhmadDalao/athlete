<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_links_to_the_password_recovery_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Request a reset link');
    }

    public function test_web_password_link_request_is_enumeration_safe_and_logged(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ]);
        $unknown = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'missing@example.test',
        ]);

        $known->assertRedirect(route('password.request'));
        $unknown->assertRedirect(route('password.request'));
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('email_logs', [
            'recipient' => $user->email,
            'type' => 'password_reset',
            'status' => 'sent',
        ]);
        $this->assertSame(1, EmailLog::query()->count());
    }

    public function test_web_password_reset_changes_password_revokes_tokens_and_writes_audit_log(): void
    {
        $user = User::factory()->create();
        $user->createToken('Old Android phone');
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(AuditLog::query()->where('action', 'password.reset')->where('user_id', $user->id)->exists());
    }

    public function test_api_password_link_request_is_enumeration_safe(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->postJson('/api/v1/auth/password/forgot', ['email' => $user->email]);
        $unknown = $this->postJson('/api/v1/auth/password/forgot', ['email' => 'missing@example.test']);

        $known->assertOk()->assertJsonStructure(['data' => ['message'], 'meta', 'links']);
        $unknown->assertOk()->assertJsonStructure(['data' => ['message'], 'meta', 'links']);
        $this->assertSame($known->json('data.message'), $unknown->json('data.message'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_api_password_reset_changes_password_and_rejects_an_invalid_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('Old iPhone');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'api-password-123',
            'password_confirmation' => 'api-password-123',
        ])->assertOk()->assertJsonPath('data.message', 'Password updated. Log in with your new password.');

        $this->assertTrue(Hash::check('api-password-123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/v1/auth/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'another-password-123',
            'password_confirmation' => 'another-password-123',
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['email']]]);
    }
}
