<?php

namespace App\Services;

use App\Enums\DeviceConnectionStatus;
use App\Models\DeviceConnection;

class DeviceConnectionHealthReviewService
{
    /**
     * @return array{
     *     severity:string,
     *     issue:string,
     *     recommendation:string,
     *     lastErrorMessage:?string,
     *     lastErrorAt:?string,
     *     staleHours:?int,
     *     staleDays:?int,
     *     syncFailuresCount:int
     * }
     */
    public function review(DeviceConnection $connection): array
    {
        $staleHours = $connection->last_synced_at ? (int) $connection->last_synced_at->diffInHours(now()) : null;
        $staleDays = $connection->last_synced_at ? (int) $connection->last_synced_at->diffInDays(now()) : null;
        $lastErrorMessage = $connection->last_error_message ? trim($connection->last_error_message) : null;

        if ($connection->status === DeviceConnectionStatus::Disconnected) {
            return $this->payload(
                severity: 'high',
                issue: 'Connection is disconnected.',
                recommendation: 'Reconnect the provider before the recovery signal goes blind.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: $lastErrorMessage,
            );
        }

        if ($lastErrorMessage) {
            return $this->payload(
                severity: 'high',
                issue: 'Recent sync failed.',
                recommendation: $connection->usesOauth()
                    ? 'Reconnect or rerun the provider sync after fixing the token problem.'
                    : 'Check the ingest sender and push a fresh payload.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: $lastErrorMessage,
            );
        }

        if ($connection->usesOauth() && ! $connection->refresh_token) {
            return $this->payload(
                severity: 'high',
                issue: 'OAuth refresh token is missing.',
                recommendation: 'Reconnect the account. Without a refresh token this link will die quietly.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: null,
            );
        }

        if ($connection->token_expires_at?->isPast()) {
            return $this->payload(
                severity: 'high',
                issue: 'OAuth access token is expired.',
                recommendation: 'Reconnect the account or refresh the token before the next sync window.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: null,
            );
        }

        if (! $connection->last_synced_at) {
            return $this->payload(
                severity: 'medium',
                issue: 'Connection has never synced.',
                recommendation: 'Run the first sync before anyone trusts the dashboard.',
                connection: $connection,
                staleHours: null,
                staleDays: null,
                lastErrorMessage: null,
            );
        }

        if ($staleHours !== null && $staleHours >= 24) {
            return $this->payload(
                severity: 'medium',
                issue: 'Sync is stale.',
                recommendation: 'Run the sync job and check why fresh data has not landed in the last 24 hours.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: null,
            );
        }

        if (! $connection->latestSnapshot) {
            return $this->payload(
                severity: 'medium',
                issue: 'No normalized snapshot exists yet.',
                recommendation: 'Inspect the raw provider payload and confirm the data is mapping into snapshots.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: null,
            );
        }

        if ($connection->status === DeviceConnectionStatus::Attention) {
            return $this->payload(
                severity: 'medium',
                issue: 'Connection is flagged for attention.',
                recommendation: 'Review the latest ingest or OAuth sync before it turns into a blind spot.',
                connection: $connection,
                staleHours: $staleHours,
                staleDays: $staleDays,
                lastErrorMessage: null,
            );
        }

        return $this->payload(
            severity: 'stable',
            issue: 'Connection looks healthy.',
            recommendation: 'Keep the sync cadence running.',
            connection: $connection,
            staleHours: $staleHours,
            staleDays: $staleDays,
            lastErrorMessage: null,
        );
    }

    /**
     * @return array{
     *     severity:string,
     *     issue:string,
     *     recommendation:string,
     *     lastErrorMessage:?string,
     *     lastErrorAt:?string,
     *     staleHours:?int,
     *     staleDays:?int,
     *     syncFailuresCount:int
     * }
     */
    private function payload(
        string $severity,
        string $issue,
        string $recommendation,
        DeviceConnection $connection,
        ?int $staleHours,
        ?int $staleDays,
        ?string $lastErrorMessage,
    ): array {
        return [
            'severity' => $severity,
            'issue' => $issue,
            'recommendation' => $recommendation,
            'lastErrorMessage' => $lastErrorMessage,
            'lastErrorAt' => $connection->last_error_at?->toDateTimeString(),
            'staleHours' => $staleHours,
            'staleDays' => $staleDays,
            'syncFailuresCount' => (int) $connection->sync_failures_count,
        ];
    }
}
