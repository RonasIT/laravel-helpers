<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Queue;
use RonasIT\Support\Exceptions\ModelFactoryNotFound;
use RonasIT\Support\Tests\Support\Mock\Jobs\TestJob;
use RonasIT\Support\Traits\TestingTrait;

class TestingTraitTest extends TestCase
{
    use TestingTrait;

    public function testAssertExceptionThrew(): void
    {
        $this->assertExceptionThrew(ModelFactoryNotFound::class, 'full error message');

        throw new ModelFactoryNotFound('full error message');
    }

    public function testAssertExceptionThrewNotStrictly(): void
    {
        $this->assertExceptionThrew(
            expectedClassName: ModelFactoryNotFound::class,
            expectedMessage: 'error',
            isStrict: false,
        );

        throw new ModelFactoryNotFound('full error message');
    }

    public function testAssertQueueEqualsFixture(): void
    {
        Queue::fake();

        for ($index = 1; $index <= 3; $index++) {
            TestJob::dispatch($index, "title_{$index}");
        }

        $this->assertQueueEqualsFixture('test_queue_state');
    }
}
