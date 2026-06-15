<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\SignupMethod;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class UserIndexController extends Controller
{
    public function __invoke(): Response
    {
        /** @var User $viewer */
        $viewer = request()->user()->loadMissing('roles');

        abort_unless($viewer->hasRole(RoleName::Admin), 403);

        $search = request()->string('q')->trim()->value() ?: null;
        $role = request()->string('role')->value() ?: null;
        $channel = request()->string('channel')->value() ?: null;
        $allowedRoles = collect(RoleName::cases())->map->value->all();
        $allowedChannels = collect(SignupMethod::cases())->map->value->all();

        $roleFilter = in_array($role, $allowedRoles, true) ? $role : null;
        $channelFilter = in_array($channel, $allowedChannels, true) ? $channel : null;

        $baseQuery = User::query()
            ->with(['roles', 'memberships.plan'])
            ->withCount([
                'memberships',
                'deviceConnections',
                'coachAssignments as active_athlete_count' => fn (Builder $query) => $query->where('status', CoachAthleteStatus::Active->value),
            ])
            ->when($search, function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested
                        ->where('name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('primary_goal', 'like', "%{$value}%");
                });
            })
            ->when($roleFilter, fn (Builder $query, string $value) => $query->role($value))
            ->when($channelFilter, fn (Builder $query, string $value) => $query->where('registration_channel', $value));

        $users = (clone $baseQuery)
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(function (User $user): array {
                $currentMembership = $user->currentMembership();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'primaryGoal' => $user->primary_goal,
                    'preferredContactMethod' => $user->preferred_contact_method,
                    'registrationChannel' => $user->registration_channel,
                    'emailVerifiedAt' => $user->email_verified_at?->toDateTimeString(),
                    'phoneVerifiedAt' => $user->phone_verified_at?->toDateTimeString(),
                    'roles' => $user->roleNames(),
                    'primaryRole' => $user->primaryRoleName(),
                    'membershipsCount' => $user->memberships_count,
                    'deviceConnectionsCount' => $user->device_connections_count,
                    'currentMembership' => $currentMembership ? [
                        'status' => $currentMembership->status->value,
                        'planName' => $currentMembership->plan?->name ?? 'Custom plan',
                        'daysRemaining' => $currentMembership->daysRemaining(),
                    ] : null,
                    'activeAthleteCount' => $user->hasRole(RoleName::Coach) ? $user->active_athlete_count : 0,
                    'createdAt' => $user->created_at?->toDateString(),
                ];
            });

        return Inertia::render('admin/users/index', [
            'filters' => [
                'q' => $search,
                'role' => $roleFilter,
                'channel' => $channelFilter,
            ],
            'summary' => [
                'totalUsers' => User::query()->count(),
                'admins' => User::query()->role(RoleName::Admin)->count(),
                'coaches' => User::query()->role(RoleName::Coach)->count(),
                'athletes' => User::query()->role(RoleName::Athlete)->count(),
                'phoneFirst' => User::query()->where('preferred_contact_method', 'phone')->count(),
                'emailVerified' => User::query()->whereNotNull('email_verified_at')->count(),
                'newThisMonth' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'users' => $users,
            'roleOptions' => collect(RoleName::cases())
                ->map(fn (RoleName $case) => ['value' => $case->value, 'label' => $case->label()])
                ->values()
                ->all(),
            'channelOptions' => collect(SignupMethod::cases())
                ->map(fn (SignupMethod $case) => ['value' => $case->value, 'label' => $case->label()])
                ->values()
                ->all(),
        ]);
    }
}
