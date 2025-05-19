<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Exceptions\UnknownRequestMethodException;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Traits\ExceptionsTestTrait;
use RonasIT\Support\Traits\MockTrait;
use RonasIT\Support\Traits\TestingTrait;

class TestingTraitTest extends TestCase
{
    use TestingTrait;

    public function testAssertExceptionThrew(): void
    {
        $this->assertExceptionThrew(UnknownRequestMethodException::class, "Unknown request method 'unsupported'");

        app(HttpRequestService::class)->send('unsupported', 'https://some.url.com');
    }

    public function testAssertExceptionThrewNotStrictly(): void
    {
        $this->assertExceptionThrew(
            className: UnknownRequestMethodException::class,
            message: 'Unknown request method',
            isStrinct: false
        );

        app(HttpRequestService::class)->send('unsupported', 'https://some.url.com');
    }
}