<?php

namespace RonasIT\Support\Tests;

use Illuminate\Foundation\Application;
use RonasIT\Support\Traits\FixturesTrait;
use Illuminate\Foundation\Testing\TestCase as BaseTest;

class HelpersTestCase extends BaseTest
{
    use FixturesTrait;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected $globalExportMode = false;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }
}
