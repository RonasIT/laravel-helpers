<?php

namespace RonasIT\Support\Tests;

use ReflectionProperty;
use RonasIT\Support\Services\EntityService;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use BadMethodCallException;

class EntityServiceTest extends HelpersTestCase
{
    use MockTrait;

    protected EntityService $entityServiceClass;
    protected ReflectionProperty $repositoryProperty;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityServiceClass = new EntityService();

        $this->repositoryProperty = new ReflectionProperty(EntityService::class, 'repository');
        $this->repositoryProperty->setAccessible(true);
    }

    public function testSetRepository()
    {
        $this->entityServiceClass->setRepository(TestRepository::class);

        $this->assertTrue($this->repositoryProperty->getValue($this->entityServiceClass) instanceof TestRepository);
    }

    public function testCallRepositoryMethod()
    {
        $this->entityServiceClass->setRepository(TestRepository::class);

        $result = $this->entityServiceClass->getUser();

        $this->assertSame('Correct result', $result);
    }

    public function testCallNotExistsRepositoryMethod()
    {
        $this->expectException(BadMethodCallException::class);

        $this->entityServiceClass->setRepository(TestRepository::class);

        $this->entityServiceClass->getSomething();

        $this->expectExceptionMessage('Method getSomething does not exists in {$className}.');
    }
}
