<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Exceptions\ModelFactoryNotFound;
use RonasIT\Support\Traits\TestingTrait;

class TestingTraitTest extends TestCase
{
    use TestingTrait;

    public function testAssertExceptionThrew(): void
    {
        $this->assertExceptionThrew(ModelFactoryNotFound::class, 'full error message');

        throw new ModelFactoryNotFound('full error message');
    }

    public function testAssertExceptionThrewNotStrictly(): void
    {
        $this->assertExceptionThrew(
            expectedClassName: ModelFactoryNotFound::class,
            expectedMessage: 'error',
            isStrict: false
        );

        throw new ModelFactoryNotFound('full error message');
    }
}