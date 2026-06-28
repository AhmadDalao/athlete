<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AthleteAccess;
use App\Support\AthleteProfileTables;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileShowController extends Controller
{
    public function __invoke(Request $request, User $user, AthleteProfileTables $profileTables): Response
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless(AthleteAccess::canViewAthlete($viewer, $user), 404);

        $user->loadMissing(['roles', 'latestAthleteCheckIn']);

        $filters = $profileTables->filters($request);
        $options = $profileTables->options();

        return Inertia::render('athletes/show', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'primaryGoal' => $user->primary_goal,
                'preferredContactMethod' => $user->preferred_contact_method,
                'registrationChannel' => $user->registration_channel,
                'createdAt' => $user->created_at?->toDateString(),
                'latestCheckInAt' => $user->latestAthleteCheckIn?->logged_date?->toDateString(),
            ],
            'permissions' => [
                'canManageFiles' => AthleteAccess::canManageAthleteFiles($viewer, $user),
                'canViewAdminProfile' => $viewer->hasPermission('admin.users.view'),
            ],
            'filters' => $filters,
            'summary' => $profileTables->summary($viewer, $user),
            'tables' => $profileTables->tables($viewer, $user, $filters),
            'fileOptions' => $options['fileOptions'],
            'completionStatuses' => $options['completionStatuses'],
        ]);
    }
}
