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
}
