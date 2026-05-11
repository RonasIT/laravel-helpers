<?php

namespace RonasIT\Support\Tests\Support\Mock\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TestJob implements ShouldQueue
{
    use Queueable;

    public $messageGroup = null;
    public $deduplicator = null;
    public $debounceOwner = '';
    protected string $foo;

    public function __construct(
        protected int $count,
        protected string $title,
        protected bool $isPublished = true,
    ) {
        $this->onQueue('my_queue');
    }

    public function handle(): void
    {
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}
