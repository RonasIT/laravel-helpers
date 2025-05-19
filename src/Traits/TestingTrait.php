<?php

namespace RonasIT\Support\Traits;

trait TestingTrait
{
    use FixturesTrait;
    use MockTrait;
    use MailsMockTrait;

    protected function assertExceptionThrew(string $className, string $message, bool $isStrinct = true): void
    {
        $this->expectException($className);

        if ($isStrinct) {
            $message = "/^{$message}$/";
            $this->expectExceptionMessageMatches($message);
        } else {
            $this->expectExceptionMessage($message);
        }
    }
}