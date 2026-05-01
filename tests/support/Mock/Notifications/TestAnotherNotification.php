<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestAnotherNotification extends Notification
{
    public function __construct(
        public readonly string $code = 'abc-123',
        public readonly string $value = 'active',
        private readonly array $channels = ['mail', 'database'],
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDetails(): object
    {
        return (object) [
            'code' => $this->code,
            'value' => $this->value,
        ];
    }
}
