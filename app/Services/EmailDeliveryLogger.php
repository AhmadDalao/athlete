<?php

namespace App\Services;

use App\Models\EmailDeliveryLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailDeliveryLogger
{
    private const HEADER = 'X-Throughline-Mail-Log-ID';

    public function recordSending(MessageSending $event): void
    {
        try {
            $log = EmailDeliveryLog::query()->create([
                'status' => 'attempting',
                'mailer' => (string) config('mail.default'),
                'mailable' => $this->mailableName($event->data),
                'recipient' => $this->firstAddress($event->message->getTo()),
                'subject' => $event->message->getSubject(),
                'source' => $this->sourceName($event->data),
                'metadata' => $this->metadataFor($event->message, $event->data),
                'attempted_at' => now(),
            ]);

            $event->message->getHeaders()->addTextHeader(self::HEADER, (string) $log->id);
        } catch (Throwable) {
            //
        }
    }

    public function recordSent(MessageSent $event): void
    {
        try {
            /** @var Email $message */
            $message = $event->message;
            $logId = $message->getHeaders()->get(self::HEADER)?->getBodyAsString();

            if (! $logId || ! ctype_digit($logId)) {
                EmailDeliveryLog::query()->create([
                    'status' => 'sent',
                    'mailer' => (string) config('mail.default'),
                    'mailable' => $this->mailableName($event->data),
                    'message_id' => $event->sent->getMessageId(),
                    'recipient' => $this->firstAddress($message->getTo()),
                    'subject' => $message->getSubject(),
                    'source' => $this->sourceName($event->data),
                    'metadata' => $this->metadataFor($message, $event->data),
                    'attempted_at' => now(),
                    'sent_at' => now(),
                ]);

                return;
            }

            EmailDeliveryLog::query()
                ->whereKey((int) $logId)
                ->update([
                    'status' => 'sent',
                    'message_id' => $event->sent->getMessageId(),
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (Throwable) {
            //
        }
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function firstAddress(array $addresses): ?string
    {
        $address = $addresses[0] ?? null;

        return $address instanceof Address ? $address->getAddress() : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mailableName(array $data): ?string
    {
        $mailable = $data['__laravel_mailable'] ?? $data['mailable'] ?? null;

        return is_object($mailable) ? class_basename($mailable) : (is_string($mailable) ? class_basename($mailable) : null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sourceName(array $data): ?string
    {
        $source = $data['source'] ?? null;

        return is_string($source) && $source !== '' ? $source : 'laravel-mailer';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function metadataFor(Email $message, array $data): array
    {
        return [
            'from' => $this->firstAddress($message->getFrom()),
            'cc' => collect($message->getCc())->map(fn (Address $address): string => $address->getAddress())->values()->all(),
            'bcc_count' => count($message->getBcc()),
            'data_keys' => array_values(array_filter(array_keys($data), fn (string $key): bool => ! str_starts_with($key, '__'))),
        ];
    }
}
