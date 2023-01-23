<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Repositories\BaseRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;

class SearchTraitTest extends HelpersTestCase
{
    use MockTrait;

    protected BaseRepository $baseRepository;
    protected ReflectionProperty $onlyTrashedProperty;

    public function setUp(): void
    {
        parent::setUp();

        $this->baseRepository = new BaseRepository();

        $this->onlyTrashedProperty = new ReflectionProperty(BaseRepository::class, 'onlyTrashed');
        $this->onlyTrashedProperty->setAccessible('pubic');
    }

    public function testSearchQueryOnlyTrashed()
    {
        $mock = $this->mockClass(BaseRepository::class, ['searchQuery', 'onlyTrashed']);

        $mock->expects($this->once())->method('searchQuery')->with(['only_trashed' => true])->willReturnSelf();
        $mock->expects($this->once())->method('onlyTrashed')->willReturnSelf();
        $mock->searchQuery(['only_trashed' => true]);
        $mock->onlyTrashed();
    }

    public function testOnlyTrashed()
    {
        $this->baseRepository->onlyTrashed();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->baseRepository);

        $this->assertEquals(true, $onlyTrashed);
    }
}