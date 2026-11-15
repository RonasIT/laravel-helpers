<?php

namespace RonasIT\Support\Tests;

use PHPStan\Rules\Generics\ClassAncestorsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClassAncestorsRule>
 */
class GenericAncestorsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ClassAncestorsRule::class);
    }

    public function testGenericAncestors(): void
    {
        $this->analyse([
            __DIR__ . '/support/Mock/Repositories/TestRepository.php',
            __DIR__ . '/support/Mock/Repositories/TypedTestRepository.php',
            __DIR__ . '/support/Mock/Services/TypedTestService.php',
            __DIR__ . '/support/Mock/Services/UntypedTestService.php',
        ], []);
    }
}
