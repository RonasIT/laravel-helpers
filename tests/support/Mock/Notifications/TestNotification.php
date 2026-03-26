<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    public function __construct(
        public readonly string $name = 'John Smith',
        public readonly string $email = 'john@example.com',
        protected readonly int $userId = 1,
        private readonly array $channels = ['database'],
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
