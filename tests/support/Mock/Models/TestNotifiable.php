<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Notifications\Notifiable;

class TestNotifiable
{
    use Notifiable;

    public function __construct(
        public int $id = 1,
    ) {
    }

    public function getKey(): int
    {
        return $this->id;
    }
}
