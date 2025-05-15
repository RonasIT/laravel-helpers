<?php

namespace RonasIT\Support\Traits;

trait ExceptionsTestTrait
{
    protected function assertExceptionThrew(string $className, string $message): void
    {
        $message = "/^{$message}$/";

        $this->expectException($className);
        $this->expectExceptionMessageMatches($message);
    }
}