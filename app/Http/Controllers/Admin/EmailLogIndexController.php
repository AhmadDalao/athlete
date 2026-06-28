<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDeliveryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmailLogIndexController extends Controller
{
    public function __invoke(Request $request): Response|StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()->loadMissing('roles');

        abort_unless($viewer->hasPermission('admin.email_logs.view'), 403);

        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);

        if ($request->boolean('export')) {
            return $this->export((clone $query)->latest()->limit(5000)->get(), 'throughline-email-logs.csv');
        }

        $logs = (clone $query)
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmailDeliveryLog $log): array => $this->row($log));

        return Inertia::render('admin/email-logs', [
            'filters' => $filters,
            'summary' => [
                'totalAttempts' => EmailDeliveryLog::query()->count(),
                'sent' => EmailDeliveryLog::query()->where('status', 'sent')->count(),
                'attempting' => EmailDeliveryLog::query()->where('status', 'attempting')->count(),
                'failed' => EmailDeliveryLog::query()->where('status', 'failed')->count(),
            ],
            'statuses' => EmailDeliveryLog::query()
                ->select('status')
                ->distinct()
                ->orderBy('status')
                ->pluck('status')
                ->values(),
            'types' => EmailDeliveryLog::query()
                ->whereNotNull('mailable')
                ->select('mailable')
                ->distinct()
                ->orderBy('mailable')
                ->pluck('mailable')
                ->values(),
            'logs' => $logs,
        ]);
    }

    /**
     * @return array{q:string|null,status:string|null,type:string|null,date_from:string|null,date_to:string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->string('q')->trim()->value() ?: null,
            'status' => $request->string('status')->trim()->value() ?: null,
            'type' => $request->string('type')->trim()->value() ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
        ];
    }

    /**
     * @param  array{q:string|null,status:string|null,type:string|null,date_from:string|null,date_to:string|null}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return EmailDeliveryLog::query()
            ->when($filters['q'], function (Builder $query, string $value): void {
                $query->where(function (Builder $nested) use ($value): void {
                    $nested
                        ->where('recipient', 'like', "%{$value}%")
                        ->orWhere('subject', 'like', "%{$value}%")
                        ->orWhere('mailable', 'like', "%{$value}%")
                        ->orWhere('source', 'like', "%{$value}%")
                        ->orWhere('error', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'], fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['type'], fn (Builder $query, string $value) => $query->where('mailable', $value))
            ->when($filters['date_from'], fn (Builder $query, string $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'], fn (Builder $query, string $value) => $query->whereDate('created_at', '<=', $value));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(EmailDeliveryLog $log): array
    {
        return [
            'id' => $log->id,
            'time' => $log->created_at?->toDateTimeString(),
            'status' => $log->status,
            'type' => $log->mailable ?? 'System email',
            'recipient' => $log->recipient,
            'subject' => $log->subject,
            'source' => $log->source,
            'mailer' => $log->mailer,
            'messageId' => $log->message_id,
            'error' => $log->error,
            'attemptedAt' => $log->attempted_at?->toDateTimeString(),
            'sentAt' => $log->sent_at?->toDateTimeString(),
        ];
    }

    private function export($logs, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time', 'Status', 'Type', 'Recipient', 'Subject', 'Source', 'Mailer', 'Message ID', 'Error']);

            foreach ($logs as $log) {
                $row = $this->row($log);
                fputcsv($handle, [
                    $row['time'],
                    $row['status'],
                    $row['type'],
                    $row['recipient'],
                    $row['subject'],
                    $row['source'],
                    $row['mailer'],
                    $row['messageId'],
                    $row['error'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
