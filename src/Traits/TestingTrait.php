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

    protected function assertExceptionThrew(string $expectedClassName, string $expectedMessage, bool $isStrict = true): void
    {
        $this->expectException($expectedClassName);

        $expectedMessage = preg_quote($expectedMessage, '/');

        $expectedMessage = ($isStrict) ? "^{$expectedMessage}$" : $expectedMessage;

        $this->expectExceptionMessageMatches("/{$expectedMessage}/");
    }

    protected function assertQueueEqualsFixture(string $fixture, bool $exportMode = false): void
    {
        $actualData = [];

        foreach (Queue::pushedJobs() as $namespace => $jobs) {
            $actualData[$namespace] = Arr::map($jobs, fn ($job) => $this->getObjectAttributes($job['job']));
        }

        $this->assertEqualsFixture("queue_states/{$fixture}", $actualData, $exportMode);
    }

    protected function getObjectAttributes(object $object): array
    {
        $result = [];

        $properties = (new ReflectionClass($object))->getProperties();

        foreach ($properties as $property) {
            $property->isInitialized($object)
                ? $result[$property->getName()] = $property->getValue($object)
                : $result[$property->getName()] = null;
        }

        return json_decode(json_encode($result), true);
    }
}
