<?php

namespace RonasIT\Support\Traits;

use Exception;
use Illuminate\Support\Arr;
use Closure;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

trait MockTrait
{
    use PHPMock;

    protected const OPTIONAL_ARGUMENT_NAME = 'optionalParameter';

    /**
     * Mock selected class. Call chain should looks like:
     *
     * [
     *     [
     *         'function' => 'yourMethod',
     *         'arguments' => ['firstArgumentValue', 2, true],
     *         'result' => 'result_fixture.json'
     *     ],
     *     $this->functionCall('yourMethod', ['firstArgumentValue', 2, true], 'result_fixture.json')
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

        $methodsCalls = collect($callChain)->groupBy('function');

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
                    $callIndex = $this->getInvocationCount($matcher) - 1;
                    $expectedCall = $calls[$callIndex];

                    $expectedArguments = Arr::get($expectedCall, 'arguments', []);

                    $this->assertArguments(
                        $args,
                        $expectedArguments,
                        $class,
                        $method,
                        $callIndex
                    );

                    return $expectedCall['result'];
                });
        });

        $this->app->instance($class, $mock);

        return $mock;
    }

    /**
     * Mock native function. Call chain should looks like:
     *
     * [
     *     [
     *         'function' => 'function_name',
     *         'arguments' => ['firstArgumentValue', 2, true],
     *         'result' => '123'
     *     ],
     *     $this->functionCall('function_name', ['firstArgumentValue', 2, true], '123')
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
                    $callIndex = $this->getInvocationCount($matcher) - 1;
                    $expectedCall = $calls[$callIndex];

                    $expectedArguments = Arr::get($expectedCall, 'arguments', []);

                    $this->assertArguments(
                        $args,
                        $expectedArguments,
                        $namespace,
                        $function,
                        $callIndex,
                        false
                    );

                    return $expectedCall['result'];
                });
        });
    }

    protected function assertArguments(
        $actual,
        $expected,
        string $class,
        string $function,
        int $callIndex,
        bool $isClass = true
    ): void {
        $expectedCount = count($expected);
        $actualCount = count($actual);

        if ($expectedCount !== $actualCount) {
            $requiredParametersCount = count(array_filter($actual, fn ($item) => $item !== self::OPTIONAL_ARGUMENT_NAME));

            if ($expectedCount > $actualCount || $expectedCount < $requiredParametersCount) {
                throw new Exception("Failed assert that function {$function} was called with {$expectedCount} arguments, actually it calls with {$actualCount} arguments.");
            }
        }

        $expected = array_pad($expected, $actualCount, self::OPTIONAL_ARGUMENT_NAME);

        $message = ($isClass)
            ? "Class '{$class}'\nMethod: '{$function}'\nMethod call index: {$callIndex}"
            : "Namespace '{$class}'\nFunction: '{$function}'\nCall index: {$callIndex}";

        foreach ($actual as $index => $argument) {
            $this->assertEquals(
                $expected[$index],
                $argument,
                "Failed asserting that arguments are equals to expected.\n{$message}\nArgument index: {$index}"
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

    public function functionCall(string $name, array $arguments = [], $result = true): array
    {
        return [
            'function' => $name,
            'arguments' => $arguments,
            'result' => $result,
        ];
    }

    protected function getInvocationCount(InvokedCount $matcher): int
    {
        return method_exists($matcher, 'getInvocationCount')
            ? $matcher->getInvocationCount()
            : $matcher->numberOfInvocations();
    }
}
