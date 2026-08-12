<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function __construct(private readonly OrganizationContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $requestedId = $this->requestedOrganizationId($request);
        $organization = $this->resolveFor($user, $requestedId);

        if ($requestedId && ! $organization) {
            abort(403, 'You do not have access to that organization.');
        }

        $this->context->set($organization);

        if ($organization && $user->current_organization_id !== $organization->id) {
            $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();
        }

        $livewireUpdate = $request->headers->has('X-Livewire');

        try {
            return $next($request);
        } finally {
            // Persistent Livewire middleware runs before the component update. Keep
            // this request-scoped context alive until that update has completed.
            if (! $livewireUpdate) {
                $this->context->clear();
            }
        }
    }

    private function requestedOrganizationId(Request $request): ?int
    {
        $value = $request->header('X-Organization-ID');

        if (! $value && $request->hasSession()) {
            $value = $request->session()->get('active_organization_id');
        }

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function resolveFor(User $user, ?int $requestedId): ?Organization
    {
        $candidateId = $requestedId ?: $user->current_organization_id;

        if ($candidateId) {
            $organization = Organization::query()->whereKey($candidateId)->where('status', 'active')->first();

            if ($organization && ($user->isPlatformAdmin() || $user->organizationMemberships()
                ->where('organization_id', $organization->id)
                ->where('status', 'active')
                ->exists())) {
                return $organization;
            }
        }

        if ($requestedId) {
            return null;
        }

        return $user->organizations()
            ->wherePivot('status', 'active')
            ->where('organizations.status', 'active')
            ->orderBy('organizations.id')
            ->first();
    }
}
