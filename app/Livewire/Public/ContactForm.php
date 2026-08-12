<?php

namespace App\Livewire\Public;

use App\Models\ContactSubmission;
use App\Models\PlatformSetting;
use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public function mount(): void
    {
        abort_unless(PlatformSetting::enabled('public_contact_enabled', true), 404);
    }

    public function submit(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactSubmission::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'message' => $data['message'],
            'status' => 'new',
        ]);

        $this->reset(['name', 'email', 'phone', 'message']);
        session()->flash('status', 'Message received. We will contact you soon.');
    }

    public function render()
    {
        return view('livewire.public.contact-form')->layout('layouts.guest', ['title' => 'Contact']);
    }
}
