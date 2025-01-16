<?php

namespace RonasIT\Support\Tests\Support\Mock\FixutresTraitMock;

use RonasIT\Support\Tests\Support\Mock\BaseMockTrait;

trait FixtureTestTraitMock
{
    use BaseMockTrait;

    protected function mockGetLocalPath(string $fixture): void
    {
        $this->mockClass(ClassWithFixtureTraitMethods::class, [
            $this->functionCall("getLocalPath", [
                $fixture
            ],
                $fixture),
        ]);
    }
}