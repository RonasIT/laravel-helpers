<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestNotificationWithStaticProperty extends Notification
{
    public static string $staticProperty = 'static-value';

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
