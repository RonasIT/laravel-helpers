<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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

    public function testAssertQueueEqualsFixture()
    {
        Queue::fake();

        TestJob::dispatch('some payload', ['another payload']);

        $laravelMajorVersion = Str::before($this->app->version(), '.');

        $fixture = match ($laravelMajorVersion) {
            '11' => 'assert_queue_equals_v11',
            '13' => 'assert_queue_equals_v13',
            default => 'assert_queue_equals',
        };

        $this->assertQueueEqualsFixture($fixture);
    }

    public function testAssertQueueEmpty()
    {
        Queue::fake();

        $this->assertQueueEmpty();
    }
}
