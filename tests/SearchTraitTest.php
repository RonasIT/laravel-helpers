<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Repositories\BaseRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;

class SearchTraitTest extends HelpersTestCase
{
    use MockTrait;

    public function testSearchQueryWithOnlyTrashed()
    {
        $mock = $this->mockClass(BaseRepository::class, ['onlyTrashed', 'getQuery']);

        $mock->expects($this->once())->method('onlyTrashed')->willReturnSelf();
        $mock->expects($this->once())->method('getQuery');

        $mock->searchQuery(['only_trashed' => true]);
    }
}