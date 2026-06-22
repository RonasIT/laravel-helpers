<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;

trait TestingTrait
{
    use FixturesTrait;
    use MailsMockTrait;
    use MockTrait;
    use ReflectionTrait;

    protected function assertExceptionThrew(string $expectedClassName, string $expectedMessage, bool $isStrict = true): void
    {
        $this->expectException($expectedClassName);

        $expectedMessage = preg_quote($expectedMessage, '/');

        $expectedMessage = ($isStrict) ? "^{$expectedMessage}$" : $expectedMessage;

        $this->expectExceptionMessageMatches("/{$expectedMessage}/");
    }

    public function assertQueueEqualsFixture(string $fixture, bool $exportMode = false): void
    {
        $actualData = [];

        foreach (Queue::pushedJobs() as $namespace => $jobs) {
            $actualData[$namespace] = Arr::map($jobs, function ($job) {
                $job = $this->getJobObject($job);

                return $this->getObjectAttributes($job);
            });
        }

        $actualData = json_decode(json_encode($actualData), true);

        $this->assertEqualsFixture("queue_states/{$fixture}", $actualData, $exportMode);
    }

    protected function assertQueueEmpty(): void
    {
        $this->assertEquals([], Queue::pushedJobs(), 'Failed assert that faked queue is empty.');
    }

    protected function getJobObject(array $job): object
    {
        if (is_object($job['job'])) {
            return $job['job'];
        }

        $data = Arr::wrap($job['data']);
        $className = $job['job'];

        return new $className(...$data);
    }
}
