<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\FixturesTrait;

class ExportTest extends \Orchestra\Testbench\TestCase
{
    use FixturesTrait;

    public function testGetFixture()
    {
        $response = $this->getJsonFixture('export_fixture.json');

        $this->assertEqualsFixture('export_fixture.json', $response);
    }

    protected function defineEnvironment($app)
    {
        $app->setBasePath(__DIR__ . '/..');
    }
}
