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

    protected function assertQueueEqualsFixture(string $fixture, $versions = [], bool $exportMode = false): void
    {
        $actualData = [];

        foreach (Queue::pushedJobs() as $namespace => $jobs) {
            $actualData[$namespace] = Arr::map($jobs, fn ($job) => is_string($job['job'])
                ? $job
                : $this->getObjectAttributes($job['job'])
            );
        }

        if (!empty($versions)) {
            $this->assertEqualsVersionedFixture("queue_states/{$fixture}", $actualData, $versions, $exportMode);
        } else {
            $this->assertEqualsFixture("queue_states/{$fixture}", $actualData, $exportMode);
        }
    }

    protected function getObjectAttributes(object $object): array
    {
        $result = [];

        $properties = (new ReflectionClass($object))->getProperties();

        foreach ($properties as $property) {
            $value = $property->isInitialized($object) ? $property->getValue($object) : null;

            $result[$property->getName()] = $value;
        }

        return json_decode(json_encode($result), true);
    }
}
