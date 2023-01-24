<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Repositories\BaseRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;

class SearchTraitTest extends HelpersTestCase
{
    use MockTrait;

    protected BaseRepository $baseRepositoryClass;
    protected ReflectionProperty $onlyTrashedProperty;

    public function setUp(): void
    {
        parent::setUp();

        $this->baseRepositoryClass = new BaseRepository();

        $this->onlyTrashedProperty = new ReflectionProperty(BaseRepository::class, 'onlyTrashed');
        $this->onlyTrashedProperty->setAccessible('pubic');
    }

    public function testOnlyTrashed()
    {
        $this->baseRepositoryClass->onlyTrashed();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->baseRepositoryClass);

        $this->assertEquals(true, $onlyTrashed);
    }
}