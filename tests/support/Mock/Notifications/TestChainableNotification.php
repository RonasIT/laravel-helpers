<?php

namespace RonasIT\Support\Tests\Support\Mock\Notifications;

use DateTimeImmutable;
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

    public function getNotifiableKey(object $notifiable): int
    {
        return $notifiable->getKey();
    }

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('@1541934671');
    }

    public function getMalformedString(): string
    {
        return "\xB1\x31";
    }

    private function getPrivateStatus(): string
    {
        return $this->status;
    }
}
