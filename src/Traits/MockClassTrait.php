<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;

trait MockClassTrait
{
    /**
     * Mock selected class. Call chain should looks like:
     *
     * [
     *     [
     *         'method' => 'yourMethod',
     *         'arguments' => ['firstArgumentValue', 2, true],
     *         'result' => 'result_fixture.json'
     *     ],
     *     $this->methodCall('yourMethod', ['firstArgumentValue', 2, true], 'result_fixture.json')
     * ]
     *
     * @param string $class
     * @param array $callChain
     * @param bool $disableConstructor
     * @return MockObject
     */
    public function mockClass(string $class, array $callChain, bool $disableConstructor = false): MockObject
    {
        $this->app->offsetUnset($class);

        $methodsCalls = collect($callChain)->groupBy('method');

        $mock = $this
            ->getMockBuilder($class)
            ->onlyMethods($methodsCalls->keys()->toArray());

        if ($disableConstructor) {
            $mock->disableOriginalConstructor();
        }

        $mock = $mock->getMock();

        $methodsCalls->each(function ($calls, $method) use ($mock, $class) {
            $matcher = $this->exactly($calls->count());

            $mock
                ->expects($matcher)
                ->method($method)
                ->willReturnCallback(function (...$args) use ($matcher, $calls, $method, $class) {
                    $callIndex = $matcher->getInvocationCount() - 1;
                    $expectedCall = $calls[$callIndex];

                    $expectedArguments = Arr::get($expectedCall, 'arguments');

                    if (!empty($expectedArguments)) {
                        $this->assertArguments(
                            $args,
                            $expectedArguments,
                            $class,
                            $method,
                            $callIndex
                        );
                    }

                    return $expectedCall['result'];
                });
        });

        $this->app->instance($class, $mock);

        return $mock;
    }

    protected function assertArguments($actual, $expected, string $class, string $method, int $callIndex): void
    {
        foreach ($actual as $index => $argument) {
            $this->assertEquals(
                $expected[$index],
                $argument,
                "Failed asserting that arguments are equals to expected.\n" .
                "Class '{$class}'\nMethod: '{$method}'\nMethod call index: {$callIndex}\nArgument index: {$index}"
            );
        }
    }

    protected function mockNoCalls(
        string $className,
        Closure $mockCallback = null,
        $disableConstructor = false
    ): MockObject {
        $mock = $this->getMockBuilder($className);

        if (!empty($mockCallback)) {
            $mockCallback($mock);
        }

        if ($disableConstructor) {
            $mock->disableOriginalConstructor();
        }

        $mock = $mock->getMock();

        $mock
            ->expects($this->never())
            ->method($this->anything());

        $this->app->instance($className, $mock);

        return $mock;
    }

    public function methodCall(string $method, array $arguments = [], $result = true): array
    {
        return [
            'method' => $method,
            'arguments' => $arguments,
            'result' => $result,
        ];
    }
}
