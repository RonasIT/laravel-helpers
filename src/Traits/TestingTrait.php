<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;

trait TestingTrait
{
    use FixturesTrait;
    use MailsMockTrait;
    use MockTrait;

    protected function assertExceptionThrew(
        string $expectedClassName,
        string $expectedMessage,
        bool $isStrict = true,
    ): void {
        $this->expectException($expectedClassName);

        $expectedMessage = preg_quote($expectedMessage, '/');

        $expectedMessage = ($isStrict) ? "^{$expectedMessage}$" : $expectedMessage;

        $this->expectExceptionMessageMatches("/{$expectedMessage}/");
    }

    protected function assertQueueEqualsFixture(string $fixture, bool $exportMode = false): void
    {
        $actualData = [];

        foreach (Queue::pushedJobs() as $namespace => $jobs) {
            $actualData[$namespace] = Arr::map($jobs, fn ($job) => is_object($job['job'])
                ? $this->getObjectAttributes($job['job'])
                : $this->getObjectAttributes(new $job['job'](...$job['data'])),
            );
        }

        $this->assertEqualsFixture("queue_states/{$fixture}", $actualData, $exportMode);
    }

    protected function assertQueueEmpty(): void
    {
        $this->assertEquals([], Queue::pushedJobs(), 'Failed assert that faked queue is empty.');
    }

    protected function getObjectAttributes(object $object): array
    {
        $result = [];

        $properties = (new ReflectionClass($object))->getProperties();

        foreach ($properties as $property) {
            $result[$property->getName()] = $property->isInitialized($object)
                ? $property->getValue($object)
                : null;
        }

        return json_decode(json_encode($result), true);
    }
}
