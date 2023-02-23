<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\FixturesTrait;
use RonasIT\Support\HelpersServiceProvider;
use Orchestra\Testbench\TestCase as BaseTest;

class HelpersTestCase extends BaseTest
{
    use FixturesTrait;

    protected $globalExportMode = false;

    protected function getPackageProviders($app): array
    {
        return [
            HelpersServiceProvider::class
        ];
    }

    protected function defineEnvironment($app)
    {
        $app->setBasePath(__DIR__ . '/..');
    }

    protected function assertSettablePropertiesReset($class): void
    {
        $onlyTrashed = $this->onlyTrashedProperty->getValue($class);
        $withTrashed = $this->withTrashedProperty->getValue($class);
        $forceMode = $this->forceModeProperty->getValue($class);
        $attachedRelations = $this->attachedRelationsProperty->getValue($class);
        $attachedRelationsCount = $this->attachedRelationsCountProperty->getValue($class);

        $this->assertEquals(false, $onlyTrashed);
        $this->assertEquals(false, $withTrashed);
        $this->assertEquals(false, $forceMode);
        $this->assertEquals([], $attachedRelations);
        $this->assertEquals([], $attachedRelationsCount);
    }
}
