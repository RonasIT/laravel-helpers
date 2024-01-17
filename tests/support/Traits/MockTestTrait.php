<?php

namespace RonasIT\Support\Tests\Support\Traits;

use PHPUnit\Framework\MockObject\MockObject;

trait MockTestTrait
{
    protected function mockClass($className, $methods = [], $disableConstructor = false): MockObject
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
}
