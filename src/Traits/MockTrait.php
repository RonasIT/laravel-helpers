<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Support\Arr;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

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
        bool $isClass = true,
    ): void {
        $reflection = ($isClass)
            ? new ReflectionMethod($class, $function)
            : new ReflectionFunction($function);

        $reflectionArgs = $reflection->getParameters();

        $this->assertArgumentsCount($actual, $expected, $reflectionArgs, $function);

        $this->fillOptionalArguments($reflectionArgs, $actual, $expected, $isClass);

        $message = ($isClass)
            ? "Class '{$class}'\nMethod: '{$function}'\nMethod call index: {$callIndex}"
            : "Namespace '{$class}'\nFunction: '{$function}'\nCall index: {$callIndex}";

        $this->compareArguments($actual, $expected, $message);
    }

    protected function assertArgumentsCount(array $actual, array $expected, array $reflectionArgs, string $function): void
    {
        $expectedCount = count($expected);
        $actualCount = count($actual);
        $requiredParametersCount = count(array_filter($reflectionArgs, fn ($param) => !$param->isOptional()));

        if ($expectedCount !== $actualCount) {
            $this->assertFalse(
                $expectedCount < $requiredParametersCount,
                "Failed assert that function {$function} was called with {$expectedCount} arguments, actually it has {$requiredParametersCount} required arguments."
            );

            $this->assertFalse(
                $expectedCount > $actualCount,
                "Failed assert that function {$function} was called with {$expectedCount} arguments, actually has {$actualCount} arguments."
            );
        }
    }

    protected function fillOptionalArguments(array $parameters, array &$actual, array &$expected, bool $isClass): void
    {
        foreach ($parameters as $index => $parameter) {
            if (!$isClass && $actual[$index] === self::OPTIONAL_ARGUMENT_NAME) {
                $actual[$index] = $parameter->getDefaultValue();
            }

            if ($this->isSetDefaultValue($expected, $index, $parameter)) {
                $expected[$index] = $parameter->getDefaultValue();
            }
        }
    }

    protected function isSetDefaultValue(array $expected, int $index, ReflectionParameter $parameter): bool
    {
        if (!$parameter->isOptional()) {
            return false;
        }

        if (!$parameter->allowsNull()) {
            return !isset($expected[$index]);
        }

        return !array_key_exists($index, $expected);
    }

    protected function compareArguments(array $actual, array $expected, string $message): void
    {
        foreach ($actual as $index => $argument) {
            $this->assertEquals(
                $expected[$index],
                $argument,
                "Failed asserting that arguments are equal to expected.\n{$message}\nArgument index: {$index}"
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

    /**
     * Mock native function chain. Call chain can be passed as multiple arrays
     * or already prepared arrays of calls.
     *
     * @param string $namespace 
     * @param array ...$calls
     */
    public function mockNativeFunctionChain(string $namespace, array ...$calls): void
    {
        $this->mockNativeFunction(
            namespace: $namespace,
            callChain: $this->prepareCallChain($calls),
        );
    }

    protected function prepareCallChain(array $calls): array
    {
        $data = [];

        foreach ($calls as $call) {
            if (Arr::isList($call)) {
                array_push($data, ...$call);
            } else {
               array_push($data, $call);
            }
        }

        return $data;
    }
}
