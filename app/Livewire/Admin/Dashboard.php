<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\AthleteInvitation;
use App\Models\AuditLog;
use App\Models\CoachAthleteAssignment;
use App\Models\ProgressEntry;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Queries\Admin\ManagedUserQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    use AuthorizesComponentAccess;

    public function render()
    {
        $actor = Auth::user();
        $organizationId = (int) $actor->current_organization_id;
        $organizationScope = fn (Builder $query): Builder => $query->when(
            $organizationId > 0,
            fn (Builder $query) => $query->withoutGlobalScope('organization')->where('organization_id', $organizationId)
        );
        $users = $organizationId > 0
            ? ManagedUserQuery::forOrganization($organizationId)
            : ManagedUserQuery::visibleTo($actor);

        return view('livewire.admin.dashboard', [
            'stats' => [
                'users' => (clone $users)->count(),
                'coaches' => $organizationId > 0 ? ManagedUserQuery::forOrganization($organizationId, 'coach')->count() : User::where('role', 'coach')->count(),
                'athletes' => $organizationId > 0 ? ManagedUserQuery::forOrganization($organizationId, 'athlete')->count() : User::where('role', 'athlete')->count(),
                'activePrograms' => $organizationScope(TrainingProgram::query())->where('status', 'active')->count(),
                'todaySessions' => $organizationScope(TrainingSession::query())->whereDate('scheduled_on', today())->count(),
                'openInvites' => $organizationScope(AthleteInvitation::query())->where('status', 'pending')->count(),
                'assignments' => $organizationScope(CoachAthleteAssignment::query())->where('status', 'active')->count(),
                'checkIns' => $organizationScope(ProgressEntry::query())->whereDate('logged_on', '>=', now()->subDays(7))->count(),
            ],
            'recentAudits' => $actor->hasPermission('admin.audit')
                ? $organizationScope(AuditLog::query())->with('user')->latest()->limit(8)->get()
                : collect(),
            'canViewAudit' => $actor->hasPermission('admin.audit'),
        ])->layout('layouts.app', ['title' => 'Admin dashboard']);
    }

    protected function componentPermissions(): array
    {
        return ['admin.access'];
    }
}
