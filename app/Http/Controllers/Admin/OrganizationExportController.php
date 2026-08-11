<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $status = $request->string('status')->toString();
        $theme = $request->string('theme')->toString();
        $search = $request->string('search')->toString();

        $organizations = Organization::query()
            ->with('owner')
            ->withCount([
                'memberships as active_members_count' => fn (Builder $query) => $query->where('status', 'active'),
                'memberships as coaches_count' => fn (Builder $query) => $query->where('status', 'active')->where('role', 'coach'),
                'memberships as athletes_count' => fn (Builder $query) => $query->where('status', 'active')->where('role', 'athlete'),
            ])
            ->when(in_array($status, ['active', 'inactive'], true), fn (Builder $query) => $query->where('status', $status))
            ->when(in_array($theme, ['system', 'dark', 'light'], true), fn (Builder $query) => $query->where('default_theme', $theme))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('plan_key', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($organizations): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'name', 'slug', 'owner', 'owner_email', 'status', 'plan', 'theme', 'timezone', 'active_members', 'coaches', 'athletes', 'created_at']);

            foreach ($organizations as $organization) {
                fputcsv($handle, [
                    $organization->id,
                    $organization->name,
                    $organization->slug,
                    $organization->owner?->name,
                    $organization->owner?->email,
                    $organization->status,
                    $organization->plan_key,
                    $organization->default_theme,
                    $organization->timezone,
                    $organization->active_members_count,
                    $organization->coaches_count,
                    $organization->athletes_count,
                    $organization->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'throughline-organizations-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
