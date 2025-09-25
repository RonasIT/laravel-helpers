<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\ParallelTesting;
use PHPUnit\Framework\MockObject\MockObject;

trait MockTestTrait
{
    public function mockClass($className, $methods = [], $disableConstructor = false): MockObject
    {
        $builder = $this->getMockBuilder($className);

        if ($methods) {
            $builder->onlyMethods($methods);
        }

        if ($disableConstructor) {
            $builder->disableOriginalConstructor();
        }

        return $builder->getMock();
    }

    protected function mockParallelTestingToken(mixed $token): void
    {
        ParallelTesting::resolveTokenUsing(fn () => $token);
    }
}
