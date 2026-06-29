<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\DeviceConnection;
use App\Models\Membership;
use App\Models\SystemNotification;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');
        $query = $request->string('q')->trim()->value();

        return Inertia::render('search/index', [
            'query' => $query,
            'sections' => $query === '' ? $this->emptyState($viewer) : $this->sections($viewer, $query),
        ]);
    }

    /**
     * @return list<array{title:string,description:string,items:list<array<string,mixed>>}>
     */
    private function sections(User $viewer, string $query): array
    {
        return [
            $this->users($viewer, $query),
            $this->training($viewer, $query),
            $this->memberships($viewer, $query),
            $this->wearables($viewer, $query),
            $this->notifications($viewer, $query),
        ];
    }

    /**
     * @return list<array{title:string,description:string,items:list<array<string,mixed>>}>
     */
    private function emptyState(User $viewer): array
    {
        $items = [
            ['title' => 'Training', 'meta' => 'Programs, sets, reps, sessions, and logs', 'href' => '/training', 'kind' => 'Workspace'],
            ['title' => 'Progress', 'meta' => 'Food, weight, hydration, soreness, and check-ins', 'href' => '/progress', 'kind' => 'Workspace'],
        ];

        if ($viewer->hasRole(RoleName::Owner) || $viewer->hasRole(RoleName::Admin)) {
            array_unshift($items, ['title' => 'Admin dashboard', 'meta' => 'Owner and admin business operations', 'href' => '/admin/dashboard', 'kind' => 'Admin']);
            $items[] = ['title' => 'Admin users', 'meta' => 'Search accounts, roles, subscriptions, and profiles', 'href' => '/admin/users', 'kind' => 'Admin'];
            $items[] = ['title' => 'Audit log', 'meta' => 'Review system activity and admin changes', 'href' => '/admin/audit-log', 'kind' => 'Admin'];
        } elseif ($viewer->hasRole(RoleName::Coach)) {
            array_unshift($items, ['title' => 'Coach home', 'meta' => 'Your athletes, programs, schedule, and invites', 'href' => '/coach', 'kind' => 'Workspace']);
        } else {
            array_unshift($items, ['title' => 'App home', 'meta' => 'Your programs, calendar, workouts, and coach messages', 'href' => '/app', 'kind' => 'Workspace']);
        }

        return [[
            'title' => 'Quick links',
            'description' => 'Type a name, email, plan, device, program, or notification title to search the platform.',
            'items' => $items,
        ]];
    }

    /**
     * @return array{title:string,description:string,items:list<array<string,mixed>>}
     */
    private function users(User $viewer, string $query): array
    {
        $users = User::query()
            ->with('roles')
            ->when(! $viewer->hasRole(RoleName::Admin), function (Builder $builder) use ($viewer): void {
                if ($viewer->hasRole(RoleName::Coach)) {
                    $athleteIds = $this->activeAthleteIds($viewer)->push($viewer->id)->unique();
                    $builder->whereIn('id', $athleteIds);

                    return;
                }

                $builder->whereKey($viewer->id);
            })
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('primary_goal', 'like', "%{$query}%");
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'title' => $user->name,
                'meta' => trim(($user->primaryRoleName() ?? 'user').' · '.$user->email),
                'href' => $viewer->hasRole(RoleName::Admin) ? route('admin.users.show', $user, false) : route('roster.index', absolute: false),
                'kind' => 'User',
            ])
            ->values()
            ->all();

        return [
            'title' => 'Users and athletes',
            'description' => 'Profiles, coach-athlete relationships, roles, and onboarding fields.',
            'items' => $users,
        ];
    }

    /**
     * @return array{title:string,description:string,items:list<array<string,mixed>>}
     */
    private function training(User $viewer, string $query): array
    {
        $programs = TrainingProgram::query()
            ->with(['coach', 'athlete'])
            ->when(! $viewer->hasRole(RoleName::Admin), function (Builder $builder) use ($viewer): void {
                if ($viewer->hasRole(RoleName::Coach)) {
                    $builder->where('coach_id', $viewer->id);

                    return;
                }

                $builder->where('athlete_id', $viewer->id);
            })
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('title', 'like', "%{$query}%")
                    ->orWhere('goal', 'like', "%{$query}%")
                    ->orWhereHas('coach', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('athlete', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$query}%"));
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (TrainingProgram $program): array => [
                'title' => $program->title,
                'meta' => "{$program->athlete->name} · coach {$program->coach->name} · {$program->status->value}",
                'href' => route('training.index', absolute: false),
                'kind' => 'Training',
            ])
            ->values()
            ->all();

        return [
            'title' => 'Training',
            'description' => 'Programs assigned by coaches, sessions, goals, and workout plans.',
            'items' => $programs,
        ];
    }

    /**
     * @return array{title:string,description:string,items:list<array<string,mixed>>}
     */
    private function memberships(User $viewer, string $query): array
    {
        $memberships = Membership::query()
            ->visibleTo($viewer)
            ->with(['user', 'plan'])
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('status', 'like', "%{$query}%")
                    ->orWhere('billing_provider', 'like', "%{$query}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($query): void {
                        $userQuery->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%");
                    })
                    ->orWhereHas('plan', fn (Builder $planQuery) => $planQuery->where('name', 'like', "%{$query}%"));
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Membership $membership): array => [
                'title' => $membership->user->name,
                'meta' => ($membership->plan?->name ?? 'Custom plan').' · '.$membership->status->value.' · '.$this->daysLabel($membership->daysRemaining()),
                'href' => route('memberships.index', absolute: false),
                'kind' => 'Membership',
            ])
            ->values()
            ->all();

        return [
            'title' => 'Memberships',
            'description' => 'Subscription status, days remaining, renewals, and billing provider state.',
            'items' => $memberships,
        ];
    }

    /**
     * @return array{title:string,description:string,items:list<array<string,mixed>>}
     */
    private function wearables(User $viewer, string $query): array
    {
        $connections = DeviceConnection::query()
            ->with('user')
            ->when(! $viewer->hasRole(RoleName::Admin), function (Builder $builder) use ($viewer): void {
                if ($viewer->hasRole(RoleName::Coach)) {
                    $builder->whereIn('user_id', $this->activeAthleteIds($viewer)->push($viewer->id)->unique());

                    return;
                }

                $builder->where('user_id', $viewer->id);
            })
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('provider', 'like', "%{$query}%")
                    ->orWhere('status', 'like', "%{$query}%")
                    ->orWhere('external_user_id', 'like', "%{$query}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($query): void {
                        $userQuery->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%");
                    });
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DeviceConnection $connection): array => [
                'title' => $connection->user->name,
                'meta' => $connection->provider->label().' · '.$connection->status->value.' · last sync '.($connection->last_synced_at?->toDateString() ?? 'never'),
                'href' => route('wearables.index', absolute: false),
                'kind' => 'Wearable',
            ])
            ->values()
            ->all();

        return [
            'title' => 'Wearables',
            'description' => 'WHOOP and device links, sync health, and external IDs.',
            'items' => $connections,
        ];
    }

    /**
     * @return array{title:string,description:string,items:list<array<string,mixed>>}
     */
    private function notifications(User $viewer, string $query): array
    {
        $notifications = SystemNotification::query()
            ->visibleTo($viewer)
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%")
                    ->orWhere('target_role', 'like', "%{$query}%");
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (SystemNotification $notification): array => [
                'title' => $notification->title,
                'meta' => $notification->target_type.' notice · '.($notification->starts_at?->toDateString() ?? 'visible now'),
                'href' => route('notifications.index', absolute: false),
                'kind' => 'Notification',
            ])
            ->values()
            ->all();

        return [
            'title' => 'Notifications',
            'description' => 'System alerts, targeted announcements, and admin broadcasts.',
            'items' => $notifications,
        ];
    }

    private function activeAthleteIds(User $viewer)
    {
        return $viewer->coachAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->pluck('athlete_id');
    }

    private function daysLabel(?int $days): string
    {
        if ($days === null) {
            return 'no end date';
        }

        return $days === 1 ? '1 day left' : "{$days} days left";
    }
}
