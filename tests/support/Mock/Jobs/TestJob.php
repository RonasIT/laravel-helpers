<?php

namespace RonasIT\Support\Tests\Support\Mock\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $foo;

    public function __construct(
        protected int $count,
        protected string $title,
        protected bool $isPublished = true,
    ) {
    }

    public function handle(): void
    {
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}
