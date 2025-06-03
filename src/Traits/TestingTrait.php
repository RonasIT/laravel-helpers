<?php

namespace RonasIT\Support\Traits;

trait TestingTrait
{
    use FixturesTrait;
    use MockTrait;
    use MailsMockTrait;

    protected function assertExceptionThrew(string $expectedClassName, string $expectedMessage, bool $isStrict = true): void
    {
        $this->expectException($expectedClassName);

        $expectedMessage = ($isStrict) ? "^{$expectedMessage}$" : $expectedMessage;

        $this->expectExceptionMessageMatches("/{$expectedMessage}/");
    }
}