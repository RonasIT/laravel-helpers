<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RonasIT\Support\Exceptions\ModelFactoryNotFound;
use RonasIT\Support\Tests\Support\Mock\Jobs\AnotherTestJob;
use RonasIT\Support\Tests\Support\Mock\Jobs\TestJob;
use RonasIT\Support\Tests\Support\Traits\TestingTraitTestTrait;
use RonasIT\Support\Traits\TestingTrait;

class TestingTraitTest extends TestCase
{
    use TestingTrait, TestingTraitTestTrait;

    public static int $laravelMajorVersion;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2020-01-01');

        self::$laravelMajorVersion ??= (int) Str::before($this->app->version(), '.');
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

        $this->assertQueueEqualsVersionedFixture(self::$laravelMajorVersion, 'assert_queue_equals');
    }

    public function testAssertQueueEqualsFixturePushAsClassName(): void
    {
        Queue::fake();

        Queue::push(TestJob::class);

        $this->assertQueueEqualsVersionedFixture(self::$laravelMajorVersion, 'assert_queue_equals_as_class_name');
    }

    public function testAssertQueueEqualsFixturePushAsStringWithParams(): void
    {
        Queue::fake();

        Queue::push(TestJob::class, [
            'payload' => 'some payload',
            'anotherPayload' => ['another payload'],
        ]);

        $this->assertQueueEqualsVersionedFixture(self::$laravelMajorVersion, 'assert_queue_equals_as_class_name_with_params');
    }

    public function testAssertQueueEqualsFixturePushAsStringWithOneParam(): void
    {
        Queue::fake();

        Queue::push(TestJob::class, 'some payload');

        $this->assertQueueEqualsVersionedFixture(self::$laravelMajorVersion, 'assert_queue_equals_as_class_name_with_one_param');
    }

    public function testAssertQueueEqualsFixtureDifferentJobs(): void
    {
        Queue::fake();

        TestJob::dispatch('some payload', ['another payload']);
        AnotherTestJob::dispatch('some payload', ['another payload']);

        $this->assertQueueEqualsVersionedFixture(self::$laravelMajorVersion, 'assert_queue_equals_different_jobs');
    }

    public function testAssertQueueEmpty()
    {
        Queue::fake();

        $this->assertQueueEmpty();
    }
}
