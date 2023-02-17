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

    protected function assertSettableProperties(
        $expectedOnlyTrashed = false,
        $expectedWithTrashed = false,
        $expectedForceMode = false,
        $expectedAttachedRelations = [],
        $expectedAttachedRelationsCount = []
    ): void
    {
        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);
        $attachedRelations = $this->attachedRelationsProperty->getValue($this->testRepositoryClass);
        $attachedRelationsCount = $this->attachedRelationsCountProperty->getValue($this->testRepositoryClass);

        $this->assertEquals($expectedOnlyTrashed, $onlyTrashed);
        $this->assertEquals($expectedWithTrashed, $withTrashed);
        $this->assertEquals($expectedForceMode, $forceMode);
        $this->assertEquals($expectedAttachedRelations, $attachedRelations);
        $this->assertEquals($expectedAttachedRelationsCount, $attachedRelationsCount);
    }
}
