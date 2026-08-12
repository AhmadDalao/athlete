<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ThroughlineNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $organizationId,
        private readonly string $category,
        private readonly string $title,
        private readonly string $body,
        private readonly string $actionType,
        private readonly int $actionId,
        private readonly array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organizationId,
            'category' => $this->category,
            'title' => $this->title,
            'body' => $this->body,
            'action' => [
                'type' => $this->actionType,
                'id' => $this->actionId,
            ],
            'context' => $this->context,
        ];
    }
}
