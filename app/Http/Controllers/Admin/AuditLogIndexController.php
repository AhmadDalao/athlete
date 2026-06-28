<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogIndexController extends Controller
{
    public function __invoke(Request $request): Response|StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.audit_log.view'), 403);

        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);

        if ($request->boolean('export')) {
            return $this->export((clone $query)->latest()->limit(5000)->get(), 'throughline-audit-log.csv');
        }

        $logs = (clone $query)
            ->with('actor')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PlatformAuditLog $log): array => $this->row($log));

        return Inertia::render('admin/audit-log', [
            'filters' => $filters,
            'summary' => [
                'totalEvents' => PlatformAuditLog::query()->count(),
                'filteredEvents' => $logs->total(),
                'eventsToday' => PlatformAuditLog::query()->whereDate('created_at', today())->count(),
                'uniqueActors' => PlatformAuditLog::query()->whereNotNull('actor_user_id')->distinct('actor_user_id')->count('actor_user_id'),
            ],
            'actions' => PlatformAuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->values(),
            'entities' => PlatformAuditLog::query()
                ->whereNotNull('entity_type')
                ->select('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type')
                ->values(),
            'logs' => $logs,
        ]);
    }

    /**
     * @return array{q:string|null,action:string|null,entity:string|null,date_from:string|null,date_to:string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->string('q')->trim()->value() ?: null,
            'action' => $request->string('action')->trim()->value() ?: null,
            'entity' => $request->string('entity')->trim()->value() ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
        ];
    }

    /**
     * @param  array{q:string|null,action:string|null,entity:string|null,date_from:string|null,date_to:string|null}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return PlatformAuditLog::query()
            ->with('actor')
            ->when($filters['q'], function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested
                        ->where('summary', 'like', "%{$value}%")
                        ->orWhere('action', 'like', "%{$value}%")
                        ->orWhere('entity_type', 'like', "%{$value}%")
                        ->orWhere('ip_address', 'like', "%{$value}%")
                        ->orWhereHas('actor', function (Builder $actorQuery) use ($value): void {
                            $actorQuery
                                ->where('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%");
                        });
                });
            })
            ->when($filters['action'], fn (Builder $query, string $value) => $query->where('action', $value))
            ->when($filters['entity'], fn (Builder $query, string $value) => $query->where('entity_type', $value))
            ->when($filters['date_from'], fn (Builder $query, string $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'], fn (Builder $query, string $value) => $query->whereDate('created_at', '<=', $value));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(PlatformAuditLog $log): array
    {
        return [
            'id' => $log->id,
            'time' => $log->created_at?->toDateTimeString(),
            'actorName' => $log->actor?->name ?? 'System',
            'actorEmail' => $log->actor?->email,
            'action' => $log->action,
            'entityType' => $log->entity_type,
            'entityId' => $log->entity_id,
            'summary' => $log->summary,
            'ipAddress' => $log->ip_address,
        ];
    }

    private function export($logs, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time', 'User', 'Email', 'Action', 'Entity', 'Entity ID', 'Summary', 'IP']);

            foreach ($logs as $log) {
                $row = $this->row($log);
                fputcsv($handle, [
                    $row['time'],
                    $row['actorName'],
                    $row['actorEmail'],
                    $row['action'],
                    $row['entityType'],
                    $row['entityId'],
                    $row['summary'],
                    $row['ipAddress'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
