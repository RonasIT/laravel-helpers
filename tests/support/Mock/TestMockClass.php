<?php

namespace RonasIT\Support\Tests\Support\Mock;

class TestMockClass
{
    public function mockFunction(
        string $firsrRequeredParam,
        string $secondRequiredParam,
        ?string $firstOptionalParam = 'string',
        ?string $secondOptionalParam = null,
    ): string {
        return 'mockFunction';
    }
}
