<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\MockTrait;
use Exception;

class MockTraitTest extends HelpersTestCase
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
            $this->functionCall('uniqid', [], '0987654321'),
            $this->functionCall(name: 'uniqid', result: '0987654321'),
            $this->functionCall('array_slice', [[1, 2, 3, 4, 5], 2, 2], [3, 4]),
            $this->functionCall('array_slice', [[1, 2, 3, 4, 5], 2], [3, 4, 5]),
        ]);

        $this->assertEquals(2, rand(1, 5));
        $this->assertTrue(is_array(123));
        $this->assertEquals(7, rand(6, 10));
        $this->assertEquals('0987654321', uniqid());
        $this->assertEquals('0987654321', uniqid());
        $this->assertEquals([3, 4], array_slice([1, 2, 3, 4, 5], 2, 2));
        $this->assertEquals([3, 4, 5], array_slice([1, 2, 3, 4, 5], 2));
    }

    public function testAssertArgumentMismatchBetweenExpectedAndActualArguments()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed assert that function testFunction was called with 2 arguments, actually it calls with 1 arguments.');

        $this->assertArguments(['test'], ['test', ''], 'TestClass', 'testFunction', 0);
    }
}
