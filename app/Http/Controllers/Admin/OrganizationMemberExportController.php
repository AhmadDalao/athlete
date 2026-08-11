<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationMemberExportController extends Controller
{
    public function __invoke(Request $request, Organization $organization): StreamedResponse
    {
        $status = $request->string('status')->toString();
        $role = $request->string('role')->toString();
        $search = $request->string('search')->toString();

        $memberships = $organization->memberships()
            ->with('user')
            ->when(in_array($status, ['active', 'inactive'], true), fn (Builder $query) => $query->where('status', $status))
            ->when(in_array($role, ['organization_owner', 'organization_admin', 'coach', 'athlete'], true), fn (Builder $query) => $query->where('role', $role))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->orderBy('role')
            ->get();

        return response()->streamDownload(function () use ($organization, $memberships): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['organization', 'user_id', 'name', 'email', 'phone', 'platform_role', 'organization_role', 'status', 'joined_at', 'last_active_at']);

            foreach ($memberships as $membership) {
                fputcsv($handle, [
                    $organization->name,
                    $membership->user_id,
                    $membership->user->name,
                    $membership->user->email,
                    $membership->user->phone,
                    $membership->user->role,
                    $membership->role,
                    $membership->status,
                    $membership->joined_at?->toDateTimeString(),
                    $membership->last_active_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'throughline-'.str($organization->slug)->slug().'-members-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
