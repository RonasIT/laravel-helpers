<?php

namespace RonasIT\Support\Tests;

use BadMethodCallException;
use ReflectionProperty;
use RonasIT\Support\Services\EntityService;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepository;

class EntityServiceTest extends TestCase
{
    protected static EntityService $entityServiceClass;
    protected ReflectionProperty $repositoryProperty;

    public function setUp(): void
    {
        parent::setUp();

        self::$entityServiceClass ??= new EntityService();

        $this->repositoryProperty = new ReflectionProperty(EntityService::class, 'repository');
    }

    public function testSetRepository()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $this->assertTrue($this->repositoryProperty->getValue(self::$entityServiceClass) instanceof TestRepository);
    }

    public function testCallRepositoryMethod()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->getUser();

        $this->assertSame('Correct result', $result);
    }

    public function testCallRepositoryMethodReturnsSelf()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->getFilter();

        $this->assertInstanceOf(EntityService::class, $result);
    }

    public function testCallNotExistsRepositoryMethod()
    {
        $className = get_class(self::$entityServiceClass);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Method getSomething does not exists in {$className}.");

        self::$entityServiceClass->setRepository(TestRepository::class);

        self::$entityServiceClass->getSomething();
    }
}
