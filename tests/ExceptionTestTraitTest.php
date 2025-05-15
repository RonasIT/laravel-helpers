<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Exceptions\UnknownRequestMethodException;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Traits\ExceptionsTestTrait;
use RonasIT\Support\Traits\MockTrait;

class ExceptionTestTraitTest extends TestCase
{
    use MockTrait, ExceptionsTestTrait;

    public function testAssertExceptionThrew(): void
    {
        $this->assertExceptionThrew(UnknownRequestMethodException::class, "Unknown request method 'unsupported'");

        app(HttpRequestService::class)->send('unsupported', 'https://some.url.com');
    }
}