<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $role = $request->string('role')->toString();
        $search = $request->string('search')->toString();

        $users = User::query()
            ->withCount(['coachAssignments', 'athleteAssignments', 'coachPrograms', 'progressEntries'])
            ->when(in_array($role, ['owner', 'admin', 'coach', 'athlete'], true), fn (Builder $query) => $query->where('role', $role))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('primary_goal', 'like', "%{$search}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($users): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id',
                'name',
                'email',
                'phone',
                'role',
                'status',
                'primary_goal',
                'coach_programs',
                'coach_athletes',
                'athlete_coaches',
                'progress_entries',
                'created_at',
            ]);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->status,
                    $user->primary_goal,
                    $user->coach_programs_count,
                    $user->coach_assignments_count,
                    $user->athlete_assignments_count,
                    $user->progress_entries_count,
                    $user->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'throughline-users-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
