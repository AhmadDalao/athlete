<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->query($request)
            ->latest()
            ->paginate($this->pageSize($request->query('per_page'), 30));

        return $this->success(
            $notifications->getCollection()->map(fn (DatabaseNotification $notification): array => $this->row($notification))->values(),
            [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread' => $this->query($request)->whereNull('read_at')->count(),
            ],
            ['previous' => $notifications->previousPageUrl(), 'next' => $notifications->nextPageUrl()],
        );
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $this->query($request)->findOrFail($notification);
        $record->markAsRead();

        return $this->success($this->row($record->refresh()));
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $this->query($request)->whereNull('read_at')->update(['read_at' => now()]);

        return $this->success(['marked_read' => $count]);
    }

    private function query(Request $request)
    {
        return $request->user()->notifications()
            ->where('data->organization_id', app(OrganizationContext::class)->id());
    }

    private function row(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'category' => data_get($notification->data, 'category', 'update'),
            'title' => data_get($notification->data, 'title', 'Throughline update'),
            'body' => data_get($notification->data, 'body', ''),
            'action' => data_get($notification->data, 'action', []),
            'context' => data_get($notification->data, 'context', []),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
