<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Support\OrganizationContext;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use AuthorizesComponentAccess;
    use WithPagination;

    public string $filter = 'all';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function markRead(string $notificationId): void
    {
        $this->query()->findOrFail($notificationId)->markAsRead();
    }

    public function markAllRead(): void
    {
        $this->query()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function open(string $notificationId)
    {
        $notification = $this->query()->findOrFail($notificationId);
        $notification->markAsRead();

        return $this->redirect($this->actionUrl($notification), navigate: true);
    }

    public function render()
    {
        $notifications = $this->query()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(15);

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => $this->query()->whereNull('read_at')->count(),
        ])->layout('layouts.app', ['title' => 'Notifications']);
    }

    private function query()
    {
        return Auth::user()->notifications()
            ->where('data->organization_id', app(OrganizationContext::class)->id());
    }

    private function actionUrl(DatabaseNotification $notification): string
    {
        $type = data_get($notification->data, 'action.type');
        $id = (int) data_get($notification->data, 'action.id');
        $user = Auth::user();

        return match ($type) {
            'program_assignment' => $user->isCoach() ? route('coach.programs') : route('app.programs.show', $id),
            'scheduled_workout' => $user->isCoach() ? route('coach.schedule') : route('app.workouts.show', $id),
            'athlete_workout' => route('coach.athletes.show', (int) data_get($notification->data, 'context.athlete_id')),
            'conversation' => $user->isCoach()
                ? route('coach.messages', ['conversation' => $id])
                : route('app.messages', ['conversation' => $id]),
            default => $user->landingPath(),
        };
    }

    protected function componentPermissions(): array
    {
        return ['notifications.read'];
    }
}
