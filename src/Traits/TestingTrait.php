<?php

namespace RonasIT\Support\Traits;

trait TestingTrait
{
    use FixturesTrait;
    use MailsMockTrait;
    use MockTrait;

    protected function assertExceptionThrew(string $expectedClassName, string $expectedMessage, bool $isStrict = true): void
    {
        $this->expectException($expectedClassName);

        $expectedMessage = preg_quote($expectedMessage, '/');

        $expectedMessage = ($isStrict) ? "^{$expectedMessage}$" : $expectedMessage;

        $this->expectExceptionMessageMatches("/{$expectedMessage}/");
    }
}
