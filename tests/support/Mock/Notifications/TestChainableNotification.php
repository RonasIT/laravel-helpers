<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestChainableNotification extends Notification
{
    public function __construct(
        public readonly string $status = 'active',
        private readonly array $channels = ['database'],
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDetails(): object
    {
        return (object) ['status' => $this->status];
    }
}
