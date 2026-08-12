<?php

namespace App\Services;

use App\Models\AthleteInvitation;
use App\Models\EmailLog;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Mail;

class InvitationDeliveryService
{
    public function send(AthleteInvitation $invitation): bool
    {
        $invitation->loadMissing('coach');

        $appName = PlatformSetting::get('app_name', 'Throughline');
        $subject = PlatformSetting::get('invite_email_subject', "{$appName} athlete invitation");
        $body = PlatformSetting::get('invite_email_body', "Coach {coach_name} invited you to {app_name}.\n\nAccept here: {invite_link}");
        $fromName = PlatformSetting::get('mail_from_name');
        $fromAddress = PlatformSetting::get('mail_from_address');
        $link = route('invites.accept', $invitation->token);

        $messageBody = strtr($body, [
            '{app_name}' => $appName,
            '{coach_name}' => $invitation->coach?->name ?? 'your coach',
            '{athlete_name}' => $invitation->name ?: 'athlete',
            '{invite_link}' => $link,
            '{expires_at}' => $invitation->expires_at->format('Y-m-d'),
        ]);

        try {
            Mail::raw($messageBody, function ($message) use ($invitation, $subject, $fromName, $fromAddress): void {
                if ($fromAddress) {
                    $message->from($fromAddress, $fromName ?: null);
                }

                $message->to($invitation->email)->subject($subject);
            });

            EmailLog::create([
                'recipient' => $invitation->email,
                'subject' => $subject,
                'type' => 'athlete_invite',
                'status' => 'sent',
            ]);

            return true;
        } catch (\Throwable $exception) {
            EmailLog::create([
                'recipient' => $invitation->email,
                'subject' => $subject,
                'type' => 'athlete_invite',
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
