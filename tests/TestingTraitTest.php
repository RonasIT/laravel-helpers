<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use RonasIT\Support\Exceptions\ModelFactoryNotFound;
use RonasIT\Support\Tests\Support\Mock\Jobs\AnotherTestJob;
use RonasIT\Support\Tests\Support\Mock\Jobs\TestJob;
use RonasIT\Support\Traits\TestingTrait;

class TestingTraitTest extends TestCase
{
    use TestingTrait;

    public static int $laravelMajorVersion;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2020-01-01');
    }

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

        TestJob::dispatch('some payload', ['another payload'])->delay(now()->addMinute());

        $this->assertQueueEqualsVersioningFixture('assert_queue_equals', [13]);
    }

    public function testAssertQueueEqualsFixturePushAsStringWithParams(): void
    {
        Queue::fake();

        Queue::push(TestJob::class, [
            'payload' => 'some payload',
            'anotherPayload' => ['another payload'],
        ]);

        $this->assertQueueEqualsVersioningFixture('assert_queue_equals_as_class_name_with_params');
    }

    public function testAssertQueueEqualsFixturePushAsStringWithOneParam(): void
    {
        Queue::fake();

        Queue::push(TestJob::class, 'some payload');

        $this->assertQueueEqualsVersioningFixture('assert_queue_equals_as_class_name_with_one_param', [13]);
    }

    public function testAssertQueueEqualsFixtureDifferentJobs(): void
    {
        Queue::fake();

        TestJob::dispatch('some payload', ['another payload']);
        AnotherTestJob::dispatch('some payload', ['another payload']);

        $this->assertQueueEqualsVersioningFixture('assert_queue_equals_different_jobs', [13]);
    }

    public function testAssertQueueEmpty()
    {
        Queue::fake();

        $this->assertQueueEmpty();
    }
}
