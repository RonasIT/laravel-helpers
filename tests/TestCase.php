<?php

namespace RonasIT\Support\Tests;

use Illuminate\Testing\TestResponse;
use ReflectionClass;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\HelpersServiceProvider;
use RonasIT\Support\Traits\MailsMockTrait;
use Orchestra\Testbench\TestCase as BaseTest;

class TestCase extends BaseTest
{
    use MailsMockTrait;

    protected ?VersionEnumContract $apiVersion;

    public function setUp(): void
    {
        parent::setUp();

        putenv('FAIL_EXPORT_JSON=true');
    }

    protected function getPackageProviders($app): array
    {
        return [
            HelpersServiceProvider::class,
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

        $this->assertFalse($onlyTrashed);
        $this->assertFalse($withTrashed);
        $this->assertFalse($forceMode);
        $this->assertEquals([], $attachedRelations);
        $this->assertEquals([], $attachedRelationsCount);
    }

    public function getLoginSession($session, $guard = 'session'): array
    {
        return array_filter(
            array: $session->all(),
            callback: fn ($key) => strpos($key, "login_{$guard}_") === 0,
            mode: ARRAY_FILTER_USE_KEY,
        );
    }

    protected function getProtectedProperty(ReflectionClass $reflectionClass, string $methodName, $objectInstance)
    {
        $property = $reflectionClass->getProperty($methodName);

        return $property->getValue($objectInstance);
    }

    public function withoutAPIVersion(): self
    {
        return $this->setAPIVersion(null);
    }

    public function setAPIVersion(?VersionEnumContract $apiVersion): self
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    public function json($method, $uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        $version = (is_null($this->apiVersion)) ? '' : "/v{$this->apiVersion->value}";

        return parent::json($method, "{$version}{$uri}", $data, $headers);
    }
}
