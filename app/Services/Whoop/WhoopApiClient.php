<?php

namespace App\Services\Whoop;

use Illuminate\Support\Facades\Http;

class WhoopApiClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.whoop.client_id'))
            && filled(config('services.whoop.client_secret'));
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        /** @var list<string> $scopes */
        $scopes = config('services.whoop.scopes', []);

        return $scopes;
    }

    public function authorizationUrl(string $state): string
    {
        return sprintf(
            '%s?%s',
            rtrim((string) config('services.whoop.auth_url'), '/'),
            http_build_query([
                'response_type' => 'code',
                'client_id' => config('services.whoop.client_id'),
                'redirect_uri' => $this->redirectUri(),
                'scope' => implode(' ', $this->scopes()),
                'state' => $state,
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $code): array
    {
        return $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchBasicProfile(string $accessToken): array
    {
        return $this->get($accessToken, '/developer/v2/user/profile/basic');
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchBodyMeasurements(string $accessToken): array
    {
        return $this->get($accessToken, '/developer/v2/user/measurement/body');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchCycles(string $accessToken, string $start, string $end): array
    {
        return $this->paginate($accessToken, '/developer/v2/cycle', compact('start', 'end'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchRecoveries(string $accessToken, string $start, string $end): array
    {
        return $this->paginate($accessToken, '/developer/v2/recovery', compact('start', 'end'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchSleepActivities(string $accessToken, string $start, string $end): array
    {
        return $this->paginate($accessToken, '/developer/v2/activity/sleep', compact('start', 'end'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchWorkouts(string $accessToken, string $start, string $end): array
    {
        return $this->paginate($accessToken, '/developer/v2/activity/workout', compact('start', 'end'));
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenRequest(array $form): array
    {
        return Http::asForm()
            ->acceptJson()
            ->withBasicAuth(
                (string) config('services.whoop.client_id'),
                (string) config('services.whoop.client_secret'),
            )
            ->post((string) config('services.whoop.token_url'), $form)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $accessToken, string $path, array $query = []): array
    {
        return Http::acceptJson()
            ->withToken($accessToken)
            ->get($this->url($path), array_filter($query, fn ($value) => $value !== null && $value !== ''))
            ->throw()
            ->json();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paginate(string $accessToken, string $path, array $query = []): array
    {
        $records = [];
        $nextToken = null;

        do {
            $payload = $this->get($accessToken, $path, array_merge($query, [
                'limit' => 25,
                'nextToken' => $nextToken,
            ]));

            $records = array_merge($records, $payload['records'] ?? []);
            $nextToken = $payload['next_token'] ?? null;
        } while ($nextToken);

        return $records;
    }

    private function redirectUri(): string
    {
        return (string) (config('services.whoop.redirect') ?: route('wearables.whoop.callback', absolute: true));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.whoop.base_url'), '/').$path;
    }
}
