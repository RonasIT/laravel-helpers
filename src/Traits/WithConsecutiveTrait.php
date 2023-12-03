<?php

namespace RonasIT\Support\Traits;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

/**
 * This is a replacement for InvocationMocker::withConsecutive() which is
 * deprecated and will not be available in PHPUnit 10.
 */
trait WithConsecutiveTrait
{
    /**
     * @param array<mixed> $firstCallArguments
     * @param array<mixed> ...$consecutiveCallsArguments
     *
     * @return iterable<Callback<mixed>>
     */
    public static function withConsecutive(array $firstCallArguments, array ...$consecutiveCallsArguments): iterable
    {
        foreach ($consecutiveCallsArguments as $consecutiveCallArguments) {
            TestCase::assertSameSize(
                $firstCallArguments,
                $consecutiveCallArguments,
                'Each expected arguments list need to have the same size.'
            );
        }

        $allConsecutiveCallsArguments = [$firstCallArguments, ...$consecutiveCallsArguments];

        $numberOfArguments = count($firstCallArguments);
        $argumentList = [];

        for ($argumentPosition = 0; $argumentPosition < $numberOfArguments; ++$argumentPosition) {
            $argumentList[$argumentPosition] = array_column($allConsecutiveCallsArguments, $argumentPosition);
        }

        $mockedMethodCall = 0;
        $callbackCall = 0;

        foreach ($argumentList as $index => $argument) {
            yield new Callback(
                static function ($actualArgument) use (
                    $argumentList,
                    &$mockedMethodCall,
                    &$callbackCall,
                    $index,
                    $numberOfArguments
                ): bool {
                    $expected = $argumentList[$index][$mockedMethodCall] ?? null;

                    ++$callbackCall;
                    $mockedMethodCall = (int) ($callbackCall / $numberOfArguments);

                    if ($expected instanceof Constraint) {
                        TestCase::assertThat($actualArgument, $expected);
                    } else {
                        TestCase::assertEquals($expected, $actualArgument);
                    }

                    return true;
                },
            );
        }
    }
}