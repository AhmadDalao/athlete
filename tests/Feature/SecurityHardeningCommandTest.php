<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_hardening_command_creates_owner_admin_and_rotates_demo_passwords(): void
    {
        $demoAdmin = User::query()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@throughline.test',
            'password' => Hash::make('password'),
            'preferred_contact_method' => 'email',
            'registration_channel' => 'email',
        ]);
        $demoAdmin->assignRole(RoleName::Admin);
        $demoAdmin->createToken('demo-admin-token');

        $demoCoach = User::query()->create([
            'name' => 'Demo Coach',
            'email' => 'coach@throughline.test',
            'password' => Hash::make('password'),
            'preferred_contact_method' => 'email',
            'registration_channel' => 'email',
        ]);
        $demoCoach->assignRole(RoleName::Coach);
        $demoCoach->createToken('demo-coach-token');

        $this->artisan('throughline:security:lock-demo-users', [
            '--admin-email' => 'admin@athlete.ahmaddalao.com',
            '--admin-name' => 'Ahmad Dalao',
            '--password' => 'Sup3r!Secure#Owner',
        ])
            ->expectsOutput('Owner admin: admin@athlete.ahmaddalao.com')
            ->expectsOutput('Name: Ahmad Dalao')
            ->expectsOutput('Password: Sup3r!Secure#Owner')
            ->expectsOutput('Demo users rotated: 2')
            ->assertExitCode(0);

        $ownerAdmin = User::query()->where('email', 'admin@athlete.ahmaddalao.com')->firstOrFail();
        $this->assertTrue($ownerAdmin->hasRole(RoleName::Admin));
        $this->assertTrue(Hash::check('Sup3r!Secure#Owner', $ownerAdmin->password));
        $this->assertNotNull($ownerAdmin->email_verified_at);
        $this->assertSame(0, $ownerAdmin->tokens()->count());

        $demoAdmin->refresh();
        $demoCoach->refresh();

        $this->assertFalse(Hash::check('password', $demoAdmin->password));
        $this->assertFalse(Hash::check('password', $demoCoach->password));
        $this->assertSame(0, $demoAdmin->tokens()->count());
        $this->assertSame(0, $demoCoach->tokens()->count());
    }
}
