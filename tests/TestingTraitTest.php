<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Exceptions\UnknownRequestMethodException;
use RonasIT\Support\Traits\ExceptionsTestTrait;
use RonasIT\Support\Traits\TestingTrait;

class TestingTraitTest extends TestCase
{
    use TestingTrait;

    public function testAssertExceptionThrew(): void
    {
        $this->assertExceptionThrew(UnknownRequestMethodException::class, "Unknown request method 'unsupported'");

        throw new UnknownRequestMethodException('unsupported');
    }

    public function testAssertExceptionThrewNotStrictly(): void
    {
        $this->assertExceptionThrew(
            expectedClassName: UnknownRequestMethodException::class,
            expectedMessage: 'Unknown request method',
            isStrict: false
        );

        throw new UnknownRequestMethodException('unsupported');
    }
}