<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSelectionController extends Controller
{
    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();
        $allowed = $user->isPlatformAdmin() || $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($allowed && $organization->status === 'active', 403);

        $user->forceFill(['current_organization_id' => $organization->id])->save();
        $request->session()->put('active_organization_id', $organization->id);

        return redirect($user->fresh()->landingPath())
            ->with('status', "Switched to {$organization->name}.");
    }
}
