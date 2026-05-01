<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    public function __construct(
        public readonly string $firstParam = 'value-1',
        public readonly string $secondParam = 'value-2',
        protected readonly int $thirdParam = 1,
        private readonly array $channels = ['database'],
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function getFirstParam(): string
    {
        return $this->firstParam;
    }
}
