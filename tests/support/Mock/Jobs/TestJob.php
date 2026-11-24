<?php

namespace RonasIT\Support\Tests\Support\Mock\Jobs;

class TestJob extends BaseTestJob
{
    public function __construct(
        protected ?string $payload = null,
        protected array $anotherPayload = [],
    ) {
        $this->onQueue('some_queue');
    }

    public function handle(): void
    {
    }
}
