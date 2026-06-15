<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactSubmissionStoreRequest;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;

class ContactSubmissionStoreController extends Controller
{
    public function __invoke(ContactSubmissionStoreRequest $request): RedirectResponse
    {
        $user = $request->user();

        ContactSubmission::query()->create([
            'user_id' => $user?->id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'organization' => $request->validated('organization'),
            'role_interest' => $request->validated('role_interest'),
            'message' => $request->validated('message'),
            'source' => 'website_contact_page',
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now(),
        ]);

        return to_route('contact.show')->with('success', 'Your message is in. We will get back to you with something useful, not a canned reply.');
    }
}
