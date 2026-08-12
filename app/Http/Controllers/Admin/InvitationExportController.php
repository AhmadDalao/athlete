<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Queries\Admin\ManagedInvitationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvitationExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $invitations = ManagedInvitationQuery::visibleTo($request->user())
            ->with('coach')
            ->when(in_array($status, ['pending', 'accepted', 'cancelled'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('coach', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($invitations): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'athlete_name', 'athlete_email', 'coach', 'status', 'expires_at', 'accepted_at', 'cancelled_at', 'created_at']);

            foreach ($invitations as $invite) {
                fputcsv($handle, [
                    $invite->id,
                    $invite->name,
                    $invite->email,
                    $invite->coach?->name,
                    $invite->status,
                    $invite->expires_at?->toDateTimeString(),
                    $invite->accepted_at?->toDateTimeString(),
                    $invite->cancelled_at?->toDateTimeString(),
                    $invite->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'throughline-invitations-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
