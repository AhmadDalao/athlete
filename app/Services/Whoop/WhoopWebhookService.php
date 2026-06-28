<?php

namespace App\Services\Whoop;

use App\Enums\DeviceProvider;
use App\Models\DeviceConnection;
use App\Models\WhoopWebhookEvent;
use Illuminate\Support\Facades\Validator;
use JsonException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class WhoopWebhookService
{
    private const PROCESSABLE_STATUSES = ['pending', 'failed'];

    private const VALID_EVENT_TYPES = [
        'workout.updated',
        'workout.deleted',
        'sleep.updated',
        'sleep.deleted',
        'recovery.updated',
        'recovery.deleted',
    ];

    public function __construct(
        private readonly WhoopSyncService $syncService,
    ) {}

    public function webhookEnabled(): bool
    {
        return filled(config('throughline.integrations.whoop.webhook_secret'));
    }

    /**
     * @return array{event: WhoopWebhookEvent, queued: bool, duplicate: bool}
     *
     * @throws JsonException
     */
    public function handleWebhook(string $payload, ?string $signature, ?string $timestampHeader): array
    {
        if (! $this->webhookEnabled()) {
            throw new RuntimeException('WHOOP webhook signing is not configured yet.');
        }

        $timestamp = $this->normalizeTimestamp($timestampHeader);

        $this->guardTimestampFreshness($timestamp);
        $this->guardSignature($payload, $signature, $timestamp);

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $validated = Validator::make($decoded, [
            'user_id' => ['required'],
            'id' => ['required'],
            'type' => ['required', 'string', 'in:'.implode(',', self::VALID_EVENT_TYPES)],
            'trace_id' => ['required', 'string', 'max:255'],
        ])->validate();

        $whoopUserId = trim((string) $validated['user_id']);
        $connection = $this->connectionForWhoopUser($whoopUserId);

        $event = WhoopWebhookEvent::query()->firstOrCreate(
            [
                'trace_id' => trim((string) $validated['trace_id']),
            ],
            [
                'device_connection_id' => $connection?->id,
                'whoop_user_id' => $whoopUserId,
                'resource_id' => trim((string) $validated['id']),
                'event_type' => $validated['type'],
                'processing_status' => 'pending',
                'attempts' => 0,
                'received_at' => now(),
                'payload' => $decoded,
            ],
        );

        if (! $event->device_connection_id && $connection) {
            $event->forceFill([
                'device_connection_id' => $connection->id,
            ])->save();
        }

        return [
            'event' => $event->fresh(),
            'queued' => $event->wasRecentlyCreated,
            'duplicate' => ! $event->wasRecentlyCreated,
        ];
    }

    /**
     * @return array{processed:int,synced:int,ignored:int,failed:int,errors:list<string>}
     */
    public function processPendingEvents(?int $limit = 25): array
    {
        $events = WhoopWebhookEvent::query()
            ->whereIn('processing_status', self::PROCESSABLE_STATUSES)
            ->orderBy('received_at')
            ->when(
                is_int($limit) && $limit > 0,
                fn ($query) => $query->limit($limit),
            )
            ->get();

        $summary = [
            'processed' => $events->count(),
            'synced' => 0,
            'ignored' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($events as $event) {
            try {
                $outcome = $this->processEvent($event);
                $summary[$outcome]++;
            } catch (Throwable $exception) {
                $summary['failed']++;
                $summary['errors'][] = sprintf('Webhook %s failed: %s', $event->trace_id, $exception->getMessage());
            }
        }

        return $summary;
    }

    public function processEvent(WhoopWebhookEvent $event): string
    {
        $event->increment('attempts');
        $connection = $event->deviceConnection ?: $this->connectionForWhoopUser($event->whoop_user_id);

        if (! $connection) {
            $event->forceFill([
                'processing_status' => 'ignored',
                'processed_at' => now(),
                'last_error_message' => sprintf('No WHOOP connection matches WHOOP user %s.', $event->whoop_user_id),
            ])->save();

            return 'ignored';
        }

        if ($event->device_connection_id !== $connection->id) {
            $event->forceFill([
                'device_connection_id' => $connection->id,
            ])->save();
        }

        try {
            $this->syncService->syncConnection(
                $connection,
                (int) config('throughline.integrations.whoop.webhook_lookback_days', config('throughline.integrations.whoop.lookback_days', 10)),
            );

            $event->forceFill([
                'processing_status' => 'processed',
                'processed_at' => now(),
                'last_error_message' => null,
            ])->save();

            return 'synced';
        } catch (Throwable $exception) {
            $event->forceFill([
                'processing_status' => 'failed',
                'last_error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    private function connectionForWhoopUser(string $whoopUserId): ?DeviceConnection
    {
        if ($whoopUserId === '') {
            return null;
        }

        return DeviceConnection::query()
            ->where('provider', DeviceProvider::Whoop->value)
            ->where('external_user_id', $whoopUserId)
            ->first();
    }

    private function normalizeTimestamp(?string $timestampHeader): string
    {
        $timestamp = trim((string) $timestampHeader);

        if ($timestamp === '' || preg_match('/^\d+$/', $timestamp) !== 1) {
            throw new UnexpectedValueException('WHOOP webhook timestamp is missing or invalid.');
        }

        return $timestamp;
    }

    private function guardTimestampFreshness(string $timestamp): void
    {
        $toleranceSeconds = (int) config('throughline.integrations.whoop.webhook_tolerance_seconds', 300);

        if ($toleranceSeconds <= 0) {
            return;
        }

        $age = abs(now()->valueOf() - (int) $timestamp);

        if ($age > ($toleranceSeconds * 1000)) {
            throw new UnexpectedValueException('WHOOP webhook timestamp is outside the allowed window.');
        }
    }

    private function guardSignature(string $payload, ?string $signatureHeader, string $timestamp): void
    {
        $signature = trim((string) $signatureHeader);

        if ($signature === '') {
            throw new UnexpectedValueException('WHOOP webhook signature is missing.');
        }

        $expected = base64_encode(hash_hmac(
            'sha256',
            $timestamp.$payload,
            (string) config('throughline.integrations.whoop.webhook_secret'),
            true,
        ));

        if (! hash_equals($expected, $signature)) {
            throw new UnexpectedValueException('WHOOP webhook signature is invalid.');
        }
    }
}
