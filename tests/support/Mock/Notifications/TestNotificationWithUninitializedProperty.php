<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestNotificationWithUninitializedProperty extends Notification
{
    public string $uninitialized;

    public function __construct(
        public readonly string $status = 'active',
        private readonly array $channels = ['database'],
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }
}
