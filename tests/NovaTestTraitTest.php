<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\MockTrait;
use RonasIT\Support\Traits\NovaTestTrait;

class NovaTestTraitTest extends HelpersTestCase
{
    use MockTrait;
    use NovaTestTrait;

    public function testMockSingleCall()
    {
        $result = $this->novaSearchParams([
            'Badge:kyc_status' => ['Completed'],
        ]);

        $this->assertEquals($result, [
            'search' => '',
            'filters' => 'eyJCYWRnZTpreWNfc3RhdHVzIjpbIkNvbXBsZXRlZCJdfQ==',
            'perPage' => 25
        ]);
    }
}
