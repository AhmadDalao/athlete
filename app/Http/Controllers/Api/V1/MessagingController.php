<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MessageRequest;
use App\Http\Resources\Api\V1\ConversationResource;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->query($request)
            ->with(['participants', 'latestMessage.sender', 'latestMessage.attachments'])
            ->orderByDesc(Message::select('sent_at')->whereColumn('conversation_id', 'conversations.id')->latest('sent_at')->limit(1))
            ->paginate($this->pageSize($request->query('per_page')));

        return $this->paginated($conversations, ConversationResource::class);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $conversation = $this->query($request)->with('participants')->findOrFail($conversation->id);
        $messages = $conversation->messages()->with(['sender', 'attachments'])
            ->latest('sent_at')
            ->paginate($this->pageSize($request->query('per_page'), 50));
        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return $this->success([
            'conversation' => new ConversationResource($conversation),
            'messages' => MessageResource::collection($messages->getCollection()->reverse()->values()),
        ], [
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
        ], [
            'previous' => $messages->previousPageUrl(),
            'next' => $messages->nextPageUrl(),
        ]);
    }

    public function store(MessageRequest $request, Conversation $conversation): JsonResponse
    {
        $conversation = $this->query($request)->findOrFail($conversation->id);
        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => trim($request->validated('body')),
            'sent_at' => now(),
        ])->load(['sender', 'attachments']);
        $conversation->touch();
        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return $this->success(new MessageResource($message), status: 201);
    }

    private function query(Request $request): Builder
    {
        $organizationId = app(OrganizationContext::class)->id();

        return Conversation::query()
            ->where('organization_id', $organizationId)
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', $request->user()->id));
    }
}
