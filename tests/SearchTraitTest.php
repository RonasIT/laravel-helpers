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

    public function testSearchQueryWithOnlyTrashed()
    {
        $mock = $this->mockClass(BaseRepository::class, ['onlyTrashed', 'getQuery']);

        $mock->expects($this->once())->method('onlyTrashed')->willReturnSelf();
        $mock->expects($this->once())->method('getQuery');

        $mock->searchQuery(['only_trashed' => true]);
    }
}