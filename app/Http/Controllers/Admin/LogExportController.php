<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $tab = $request->string('tab')->toString() === 'email' ? 'email' : 'audit';

        return $tab === 'email'
            ? $this->emailExport($request)
            : $this->auditExport($request);
    }

    private function auditExport(Request $request): StreamedResponse
    {
        $search = $request->string('search')->toString();
        $action = $request->string('audit_action')->toString();
        $entity = $request->string('audit_entity')->toString();
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $logs = AuditLog::query()
            ->with('user')
            ->when($action !== '' && $action !== 'all', fn (Builder $query) => $query->where('action', $action))
            ->when($entity !== '' && $entity !== 'all', fn (Builder $query) => $query->where('entity', $entity))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('summary', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('entity', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'time', 'user', 'action', 'entity', 'entity_id', 'summary', 'ip']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toDateTimeString(),
                    $log->user?->name ?? 'System',
                    $log->action,
                    $log->entity,
                    $log->entity_id,
                    $log->summary,
                    $log->ip_address,
                ]);
            }

            fclose($handle);
        }, 'throughline-audit-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function emailExport(Request $request): StreamedResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('email_status')->toString();
        $type = $request->string('email_type')->toString();
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $logs = EmailLog::query()
            ->when($status !== '' && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($type !== '' && $type !== 'all', fn (Builder $query) => $query->where('type', $type))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('recipient', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('error', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'time', 'recipient', 'subject', 'type', 'status', 'error']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toDateTimeString(),
                    $log->recipient,
                    $log->subject,
                    $log->type,
                    $log->status,
                    $log->error,
                ]);
            }

            fclose($handle);
        }, 'throughline-email-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
