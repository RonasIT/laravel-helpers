<?php

namespace RonasIT\Support\Tests;

use ReflectionClass;
use RonasIT\Support\HelpersServiceProvider;
use RonasIT\Support\Traits\FixturesTrait;
use Orchestra\Testbench\TestCase as BaseTest;

class HelpersTestCase extends BaseTest
{
    use FixturesTrait;

    protected $globalExportMode = false;

    public function setUp(): void
    {
        parent::setUp();

        putenv('FAIL_EXPORT_JSON=true');
    }

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

    public function getLoginSession($session): array
    {
        return array_filter(
            $session->all(),
            fn ($key) => strpos($key, 'login_session_') === 0,
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function getProtectedProperty(ReflectionClass $reflectionClass, string $methodName, $objectInstance)
    {
        $property = $reflectionClass->getProperty($methodName);
        $property->setAccessible(true);

        return $property->getValue($objectInstance);
    }
}
