<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;
use phpmock\phpunit\PHPMock;

trait MockNativeFunctionTrait
{
    use PHPMock;

    /**
     * Mock native function. Call chain should looks like:
     *
     * [
     *     [
     *         'function' => 'function_name',
     *         'arguments' => ['firstArgumentValue', 2, true],
     *         'result' => '123'
     *     ]
     * ]
     *
     * @param string $namespace
     * @param array $callChain
     */
    public function mockNativeFunction(string $namespace, array $callChain)
    {
        $methodsCalls = collect($callChain)->groupBy('function');

        $methodsCalls->each(function ($calls, $function) use ($namespace) {
            $matcher = $this->exactly($calls->count());

            $mock = $this->getFunctionMock($namespace, $function);

            $mock
                ->expects($matcher)
                ->willReturnCallback(function (...$args) use ($matcher, $calls, $namespace, $function) {
                    $callIndex = $matcher->getInvocationCount() - 1;
                    $expectedCall = $calls[$callIndex];

                    $expectedArguments = Arr::get($expectedCall, 'arguments');

                    if (!empty($expectedArguments)) {
                        $this->assertArguments(
                            $args,
                            $expectedArguments,
                            $namespace,
                            $function,
                            $callIndex
                        );
                    }

                    return $expectedCall['result'];
                });
        });
        // ----

        /*$mock = $this->getFunctionMock($namespace, $function);

        $matcher = $this->exactly(count($callChain));

        $mock
            ->expects($matcher)
            ->willReturnCallback(function (...$args) use ($callChain, $matcher, $namespace, $function) {
                $callIndex = $matcher->getInvocationCount() - 1;
                $expectedCall = $callChain[$callIndex];

                $expectedArguments = Arr::get($expectedCall, 'arguments');

                if (!empty($expectedArguments)) {
                    $this->assertArguments(
                        $args,
                        $expectedArguments,
                        $namespace,
                        $function,
                        $callIndex
                    );
                }

                return $expectedCall['result'];
            });*/
    }

    protected function assertArguments($actual, $expected, string $namespace, string $function, int $callIndex): void
    {
        foreach ($actual as $index => $argument) {
            $this->assertEquals(
                $expected[$index],
                $argument,
                "Failed asserting that arguments are equals to expected.\n" .
                "Namespace '{$namespace}'\nFunction: '{$function}'\nCall index: {$callIndex}\nArgument index: {$index}"
            );
        }
    }
}
