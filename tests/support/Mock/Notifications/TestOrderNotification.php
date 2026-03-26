<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use Illuminate\Notifications\Notification;

class TestOrderNotification extends Notification
{
    public function __construct(
        public readonly int $orderId = 1,
        public readonly string $trackingNumber = '12345',
        public readonly string $status = 'shipped',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOrder(): object
    {
        return (object) [
            'id' => $this->orderId,
            'status' => $this->status,
        ];
    }
}
