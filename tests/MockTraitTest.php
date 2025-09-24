<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\ExpectationFailedException;
use RonasIT\Support\Tests\Support\Mock\TestMockClass;
use RonasIT\Support\Traits\MockTrait;

class MockTraitTest extends TestCase
{
    use MockTrait;

    public function testMockSingleCall()
    {
        $this->mockNativeFunction('RonasIT\Support\Tests', [
            $this->functionCall('random_bytes', [20], '1234567890'),
        ]);

        $this->assertEquals('1234567890', random_bytes(20));
    }

    public function testMockSeveralCalls()
    {
        $this->mockNativeFunction('RonasIT\Support\Tests', [
            $this->functionCall('rand', [0, 9], 5),
            $this->functionCall('rand', [0, 9], 4),
            $this->functionCall('rand', [0, 9], 3),
            $this->functionCall('rand', [0, 9], 2),
        ]);

        $generate = function (int $length): string {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= rand(0, 9);
            }

            return $code;
        };

        $this->assertEquals('5432', $generate(4));
    }

    public function testMockWithDifferentFunction()
    {
        $this->mockNativeFunction('RonasIT\Support\Tests', [
            $this->functionCall('rand', [1, 5], 2),
            $this->functionCall('is_array', [123]),
            $this->functionCall('rand', [6, 10], 7),
            $this->functionCall('uniqid', ['prefix'], '0987654321'),
            $this->functionCall(name: 'uniqid', result: '0987654321'),
            $this->functionCall('array_slice', [[1, 2, 3, 4, 5], 2], [3, 4, 5]),
            $this->functionCall('array_slice', [[1, 2, 3, 4, 5], 2, 2, 'preserve_keys'], [3, 4]),
        ]);

        $this->assertEquals(2, rand(1, 5));
        $this->assertTrue(is_array(123));
        $this->assertEquals(7, rand(6, 10));
        $this->assertEquals('0987654321', uniqid('prefix'));
        $this->assertEquals('0987654321', uniqid());
        $this->assertEquals([3, 4, 5], array_slice([1, 2, 3, 4, 5], 2));
        $this->assertEquals([3, 4], array_slice([1, 2, 3, 4, 5], 2, 2, 'preserve_keys'));
    }

    public function testMockNativeFunctionWhenLessRequiredParameters()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed assert that function array_slice was called with 1 arguments, actually it has 2 required arguments.');

        $this->mockNativeFunction('RonasIT\Support\Tests', [
            $this->functionCall(
                name: 'array_slice',
                arguments: [[1, 2, 3, 4, 5]],
                result: [3, 4],
            ),
        ]);

        array_slice([1, 2, 3, 4, 5], 2, 2);
    }

    public function testMockNativeFunctionWhenMoreExpectedParameters()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed assert that function array_slice was called with 5 arguments, actually has 4 arguments.');

        $this->mockNativeFunction('RonasIT\Support\Tests', [
            $this->functionCall(
                name: 'array_slice',
                arguments: [[1, 2, 3, 4, 5], 2, 2, 'preserve_keys', 'extra_parameter'],
                result: [3, 4],
            ),
        ]);

        array_slice([1, 2, 3, 4, 5], 2, 2, 'preserve_keys');
    }

    public function testMockNativeFunctionCheckMockedResult()
    {
        $this->mockNativeFunction('RonasIT\Support\Tests', [
            $this->functionCall(
                name: 'array_slice',
                arguments: [[1, 2, 3, 4, 5], 2, 2],
                result: [3, 4],
            ),
        ]);

        $this->assertEquals([3, 4], array_slice([1, 2, 3, 4, 5], 2, 2));
    }

    public function testmockNativeFunctionChain()
    {
        $firstCalls = [
            ['function' => 'rand', 'arguments' => [0, 9], 'result' => 1],
            ['function' => 'rand', 'arguments' => [0, 9], 'result' => 2],
        ];

        $secondCalls = [
            ['function' => 'rand', 'arguments' => [0, 9], 'result' => 3],
            ['function' => 'rand', 'arguments' => [0, 9], 'result' => 4],
        ];

        $thirdCalls = ['function' => 'rand', 'arguments' => [0, 9], 'result' => 5];

        $fourthCalls = ['function' => 'rand', 'arguments' => [0, 9], 'result' => 6];

        $this->mockNativeFunctionChain('RonasIT\Support\Tests', ...[$firstCalls, $secondCalls, $thirdCalls, $fourthCalls]);

        $generate = function (int $length): string {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= rand(0, 9);
            }

            return $code;
        };

        $this->assertEquals('123456', $generate(6));
    }

    public function testMockFunctionInClass()
    {
        $mock = $this->mockClass(TestMockClass::class, [
            $this->functionCall('mockFunction', ['firstRequired', 'secondRequired'], 'mockFunction'),
            $this->functionCall('mockFunction', ['firstRequired', 'secondRequired', 'firstOptional'], 'mockFunction'),
            $this->functionCall('mockFunction', ['firstRequired', 'secondRequired', 'firstOptional', 'secondOptional'], 'mockFunction'),
        ]);

        $this->assertEquals('mockFunction', $mock->mockFunction('firstRequired', 'secondRequired'));
        $this->assertEquals('mockFunction', $mock->mockFunction('firstRequired', 'secondRequired', 'firstOptional'));
        $this->assertEquals('mockFunction', $mock->mockFunction('firstRequired', 'secondRequired', 'firstOptional', 'secondOptional'));
    }

    public function testMockClassMethodCheckMockedResult()
    {
        $mock = $this->mockClass(TestMockClass::class, [
            $this->functionCall('mockFunction', ['firstRequired', 'secondRequired'], 'mocked_result'),
        ]);

        $this->assertEquals('mocked_result', $mock->mockFunction('firstRequired', 'secondRequired'));
    }

    public function testMockClassMethodWhenLessRequiredParameters()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed assert that function mockFunction was called with 1 arguments, actually it has 2 required arguments.');

        $this->assertArguments(
            actual: ['firstRequired', 'secondRequired', 'string', null],
            expected: ['firstRequired'],
            class: TestMockClass::class,
            function: 'mockFunction',
            callIndex: 0,
        );
    }

    public function testMockClassMethodWhenMoreExpectedParameters()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed assert that function mockFunction was called with 5 arguments, actually has 4 arguments.');

        $this->assertArguments(
            actual: ['firstRequired', 'secondRequired', 'string', null],
            expected: ['firstRequired', 'secondRequired', 'firstOptional', 'secondOptional', 'thirdOptional'],
            class: TestMockClass::class,
            function: 'mockFunction',
            callIndex: 0,
        );
    }

    public function testMockClassMethodWithSetNullForOptionalParameter(): void
    {
        $mock = $this->mockClass(TestMockClass::class, [
            $this->functionCall('mockFunction', ['firstRequired', 'secondRequired', null, 'string'], 'mockFunction'),
        ]);

        $this->assertEquals('mockFunction', $mock->mockFunction('firstRequired', 'secondRequired', null, 'string'));
    }
}
