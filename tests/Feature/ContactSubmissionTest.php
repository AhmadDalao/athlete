<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_and_contact_pages_can_be_rendered(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
            );

        $this->get(route('contact.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contact')
            );

        $this->get(route('contact.show', ['coach' => 'Nadia Stone', 'goal' => 'Strength blocks']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contact')
                ->where('prefill.requestedCoach', 'Nadia Stone')
            );
    }

    public function test_guest_can_submit_contact_form(): void
    {
        $response = $this->post(route('contact.store', absolute: false), [
            'name' => 'Jordan Miles',
            'email' => 'jordan@example.test',
            'phone' => '+15551234567',
            'organization' => 'Miles Performance',
            'role_interest' => 'coach',
            'message' => 'We need a cleaner way to manage athlete programs, payments, and wearable sync without juggling five systems.',
        ]);

        $response
            ->assertRedirect(route('contact.show', absolute: false))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Jordan Miles',
            'email' => 'jordan@example.test',
            'organization' => 'Miles Performance',
            'role_interest' => 'coach',
            'source' => 'website_contact_page',
            'status' => 'new',
        ]);
    }

    public function test_authenticated_user_submission_is_linked_to_their_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Lina Brooks',
            'email' => 'lina@example.test',
            'phone' => '+15559876543',
        ]);

        $this->actingAs($user)->post(route('contact.store', absolute: false), [
            'name' => 'Lina Brooks',
            'email' => 'lina@example.test',
            'phone' => '+15559876543',
            'organization' => 'Throughline Beta Team',
            'role_interest' => 'athlete',
            'message' => 'I want help wiring the athlete-facing flow so progress, training, and support all feel connected.',
        ])->assertRedirect(route('contact.show', absolute: false));

        $submission = ContactSubmission::query()->latest('id')->first();

        $this->assertNotNull($submission);
        $this->assertSame($user->id, $submission->user_id);
        $this->assertSame('website_contact_page', $submission->source);
        $this->assertNotNull($submission->submitted_at);
    }
}
