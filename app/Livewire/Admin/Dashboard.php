<?php

namespace App\Livewire\Admin;

use App\Models\AthleteInvitation;
use App\Models\AuditLog;
use App\Models\CoachAthleteAssignment;
use App\Models\ProgressEntry;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'coaches' => User::where('role', 'coach')->count(),
                'athletes' => User::where('role', 'athlete')->count(),
                'activePrograms' => TrainingProgram::where('status', 'active')->count(),
                'todaySessions' => TrainingSession::whereDate('scheduled_on', today())->count(),
                'openInvites' => AthleteInvitation::where('status', 'pending')->count(),
                'assignments' => CoachAthleteAssignment::where('status', 'active')->count(),
                'checkIns' => ProgressEntry::whereDate('logged_on', '>=', now()->subDays(7))->count(),
            ],
            'recentAudits' => AuditLog::with('user')->latest()->limit(8)->get(),
        ])->layout('layouts.app', ['title' => 'Admin dashboard']);
    }
}
