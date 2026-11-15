<?php

namespace RonasIT\Support\Tests;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeInferenceTest extends TypeInferenceTestCase
{
    public static function getFileAssertsData(): array
    {
        return self::gatherAssertTypesFromDirectory(__DIR__ . '/support/Types');
    }

    #[DataProvider('getFileAssertsData')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
