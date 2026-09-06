<?php

namespace RonasIT\Support\Tests\Support\Mock\Handlers;

class TestRequestHandler
{
    public static array $calls = [];

    public function __construct(protected string $name)
    {
    }

    public function __invoke(): void
    {
        static::$calls[] = $this->name;
    }
}
