<?php

namespace RonasIT\Support\Tests\Support\Traits;

trait TestingTraitTestTrait
{
    protected function assertQueueEqualsVersionedFixture(int $version, string $fixtureName, bool $exportMode = false): void
    {
        $this->assertQueueEqualsFixture("v{$version}/{$fixtureName}", $exportMode);
    }
}
