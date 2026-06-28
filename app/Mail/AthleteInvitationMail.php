<?php

namespace App\Mail;

use App\Models\AthleteInvitation;
use App\Models\PlatformSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AthleteInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AthleteInvitation $invitation,
        public readonly string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        $subject = PlatformSetting::valueFor('athlete_invite_subject')
            ?: 'You have been invited to Throughline';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.athlete-invitation',
            with: [
                'body' => PlatformSetting::valueFor('athlete_invite_body'),
                'senderLabel' => PlatformSetting::valueFor('athlete_invite_sender_label'),
            ],
        );
    }
}
