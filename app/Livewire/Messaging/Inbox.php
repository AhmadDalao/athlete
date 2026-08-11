<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Inbox extends Component
{
    use WithFileUploads;

    public string $search = '';

    public ?int $conversationId = null;

    public string $body = '';

    public $attachment = null;

    public function mount(ConversationService $service): void
    {
        abort_unless(Auth::user()->can('messages.read'), 403);

        if (request()->filled('user')) {
            $other = User::findOrFail((int) request()->integer('user'));
            $this->conversationId = $service->direct(Auth::user(), $other)->id;
        } elseif (request()->filled('conversation')) {
            $this->selectConversation((int) request()->integer('conversation'));
        } else {
            $this->conversationId = $this->conversationQuery()->value('conversations.id');
        }

        $this->markRead();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->conversationId = $this->conversationQuery()->whereKey($conversationId)->value('conversations.id');
        abort_unless($this->conversationId, 403);
        $this->markRead();
    }

    public function startConversation(int $userId, ConversationService $service): void
    {
        abort_unless(Auth::user()->can('messages.send'), 403);
        $this->conversationId = $service->direct(Auth::user(), User::findOrFail($userId))->id;
        $this->markRead();
    }

    public function closeConversation(): void
    {
        $this->conversationId = null;
    }

    public function send(): void
    {
        abort_unless(Auth::user()->can('messages.send'), 403);
        $conversation = $this->selectedConversation();
        $data = $this->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240', 'required_without:body'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'body' => trim($data['body'] ?? '') ?: 'Attachment',
            'sent_at' => now(),
        ]);

        if ($this->attachment) {
            $path = $this->attachment->store("message-attachments/{$conversation->id}", 'public');
            MediaAsset::create([
                'organization_id' => Auth::user()->current_organization_id,
                'uploaded_by' => Auth::id(),
                'attachable_type' => Message::class,
                'attachable_id' => $message->id,
                'type' => str_starts_with((string) $this->attachment->getMimeType(), 'image/') ? 'image' : 'document',
                'disk' => 'public',
                'path' => $path,
                'mime_type' => $this->attachment->getMimeType(),
                'original_name' => $this->attachment->getClientOriginalName(),
                'size' => $this->attachment->getSize(),
            ]);
        }

        $this->reset(['body', 'attachment']);
        $this->markRead();
        $this->dispatch('message-sent');
    }

    public function markRead(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversation = $this->conversationQuery()->whereKey($this->conversationId)->first();
        if ($conversation) {
            $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);
        }
    }

    public function render()
    {
        $user = Auth::user();
        $conversations = $this->conversationQuery()
            ->with(['participants', 'latestMessage.sender'])
            ->when($this->search, fn (Builder $query) => $query->whereHas('participants', fn (Builder $query) => $query
                ->where('users.id', '!=', $user->id)
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))))
            ->orderByDesc(Message::select('sent_at')->whereColumn('conversation_id', 'conversations.id')->latest('sent_at')->limit(1))
            ->get();

        $selected = $this->conversationId
            ? $this->conversationQuery()->with(['participants', 'messages.sender', 'messages.attachments'])->find($this->conversationId)
            : null;
        $candidates = $user->isCoach()
            ? User::query()->whereHas('athleteAssignments', fn (Builder $query) => $query->where('coach_id', $user->id)->where('status', 'active'))->orderBy('name')->get()
            : User::query()->whereHas('coachAssignments', fn (Builder $query) => $query->where('athlete_id', $user->id)->where('status', 'active'))->orderBy('name')->get();

        return view('livewire.messaging.inbox', [
            'conversations' => $conversations,
            'selected' => $selected,
            'candidates' => $candidates,
        ])->layout('layouts.app', ['title' => 'Messages']);
    }

    private function conversationQuery(): Builder
    {
        $organizationId = app(OrganizationContext::class)->id() ?: Auth::user()->current_organization_id;

        return Conversation::query()
            ->where('organization_id', $organizationId)
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', Auth::id()));
    }

    private function selectedConversation(): Conversation
    {
        if (! $this->conversationId) {
            throw ValidationException::withMessages(['body' => 'Select a conversation first.']);
        }

        return $this->conversationQuery()->findOrFail($this->conversationId);
    }
}
