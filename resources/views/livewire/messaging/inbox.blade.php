<div wire:poll.15s="markRead">
    <x-tl.page-hero eyebrow="Communication" title="Messages" subtitle="Direct coach-athlete conversations inside the active organization. Messages refresh every 15 seconds without pretending to be live chat." />

    <section class="tl-message-shell {{ $selected ? 'has-selection' : '' }}">
        <aside class="tl-message-list">
            <div class="tl-message-list-head">
                <div>
                    <div class="tl-eyebrow">Inbox</div>
                    <h3>Conversations</h3>
                </div>
                @can('messages.send')
                    <div class="dropdown">
                        <button class="tl-icon-button" type="button" data-bs-toggle="dropdown" aria-label="Start conversation"><i class="fa-solid fa-plus"></i></button>
                        <div class="dropdown-menu dropdown-menu-end tl-theme-menu">
                            @forelse($candidates as $candidate)
                                <button class="dropdown-item" type="button" wire:click="startConversation({{ $candidate->id }})"><span class="tl-user-avatar">{{ str($candidate->name)->substr(0, 2)->upper() }}</span><span>{{ $candidate->name }}<small class="d-block tl-muted">{{ $candidate->email }}</small></span></button>
                            @empty
                                <span class="dropdown-item-text tl-muted">No assigned contacts.</span>
                            @endforelse
                        </div>
                    </div>
                @endcan
            </div>
            <div class="p-3 border-bottom tl-border-color"><input class="form-control" type="search" wire:model.live.debounce.300ms="search" placeholder="Search conversations"></div>
            <div class="tl-message-conversations">
                @forelse($conversations as $conversation)
                    @php($other = $conversation->participants->firstWhere('id', '!=', auth()->id()))
                    <button class="tl-conversation-row {{ $conversationId === $conversation->id ? 'active' : '' }}" type="button" wire:click="selectConversation({{ $conversation->id }})" wire:key="conversation-{{ $conversation->id }}">
                        <span class="tl-user-avatar">{{ str($other?->name ?? 'TL')->substr(0, 2)->upper() }}</span>
                        <span class="min-w-0 flex-grow-1">
                            <strong>{{ $other?->name ?? 'Conversation' }}</strong>
                            <small>{{ str($conversation->latestMessage?->body ?? 'No messages yet')->limit(54) }}</small>
                        </span>
                        <time>{{ $conversation->latestMessage?->sent_at?->format('M j') }}</time>
                    </button>
                @empty
                    <div class="p-4 text-center tl-muted"><i class="fa-regular fa-comments fa-2x mb-3 d-block"></i>No conversations yet. Start with an assigned {{ auth()->user()->isCoach() ? 'athlete' : 'coach' }}.</div>
                @endforelse
            </div>
        </aside>

        <div class="tl-message-thread">
            @if($selected)
                @php($other = $selected->participants->firstWhere('id', '!=', auth()->id()))
                <header class="tl-thread-head">
                    <button class="tl-icon-button d-lg-none" type="button" wire:click="closeConversation" aria-label="Back to conversations"><i class="fa-solid fa-arrow-left"></i></button>
                    <span class="tl-user-avatar">{{ str($other?->name ?? 'TL')->substr(0, 2)->upper() }}</span>
                    <span><strong>{{ $other?->name ?? 'Conversation' }}</strong><small>{{ $other?->isCoach() ? 'Coach' : 'Athlete' }}</small></span>
                </header>
                <div class="tl-thread-messages" data-message-thread>
                    @forelse($selected->messages as $message)
                        <article class="tl-message-bubble {{ $message->sender_id === auth()->id() ? 'is-mine' : '' }}" wire:key="message-{{ $message->id }}">
                            <div>{{ $message->body }}</div>
                            @foreach($message->attachments as $file)
                                <a class="tl-message-attachment" href="{{ route('messages.attachments', $file) }}"><i class="fa-solid {{ $file->type === 'image' ? 'fa-image' : 'fa-file-lines' }}"></i><span>{{ $file->original_name ?: 'Attachment' }}</span><i class="fa-solid fa-download"></i></a>
                            @endforeach
                            <footer>{{ $message->sender?->name ?? 'Deleted user' }} · {{ $message->sent_at->format('M j, H:i') }}</footer>
                        </article>
                    @empty
                        <div class="m-auto text-center tl-muted"><i class="fa-regular fa-paper-plane fa-2x mb-3 d-block"></i>Send the first message.</div>
                    @endforelse
                </div>
                @can('messages.send')
                    <form class="tl-message-compose" wire:submit="send">
                        <label class="tl-attachment-button" title="Attach image or PDF"><i class="fa-solid fa-paperclip"></i><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" wire:model="attachment"></label>
                        <textarea class="form-control" rows="1" wire:model="body" placeholder="Write a message"></textarea>
                        <button class="btn btn-tl" type="submit"><i class="fa-solid fa-paper-plane"></i><span class="d-none d-sm-inline">Send</span></button>
                        @if($attachment)<div class="tl-message-file-name"><i class="fa-solid fa-paperclip"></i> {{ $attachment->getClientOriginalName() }}</div>@endif
                        @error('body')<div class="text-danger small">{{ $message }}</div>@enderror
                        @error('attachment')<div class="text-danger small">{{ $message }}</div>@enderror
                    </form>
                @endcan
            @else
                <div class="m-auto text-center tl-muted p-4"><i class="fa-regular fa-comments fa-3x mb-3 d-block"></i><h3 class="text-body-emphasis">Select a conversation</h3><p>Choose a thread or start a new one with an assigned contact.</p></div>
            @endif
        </div>
    </section>
</div>
