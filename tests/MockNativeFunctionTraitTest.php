<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\MockNativeFunctionTrait;

class MockNativeFunctionTraitTest extends HelpersTestCase
{
    use MockNativeFunctionTrait;

    public function testMockSingleCall()
    {
        $this->mockNativeFunction('RonasIT\Support\Tests', [
            [
                'function' => 'random_bytes',
                'arguments' => [20],
                'result' => '1234567890',
            ]
        ]);

        $this->assertEquals('1234567890', random_bytes(20));
    }

    public function testMockSeveralCalls()
    {
        $this->mockNativeFunction('RonasIT\Support\Tests', [
            [
                'function' => 'rand',
                'arguments' => [0, 9],
                'result' => 5,
            ],
            [
                'function' => 'rand',
                'arguments' => [0, 9],
                'result' => 4,
            ],
            [
                'function' => 'rand',
                'arguments' => [0, 9],
                'result' => 3,
            ],
            [
                'function' => 'rand',
                'arguments' => [0, 9],
                'result' => 2,
            ],
        ]);

        $generate = function(int $length): string {
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
            [
                'function' => 'rand',
                'arguments' => [1, 5],
                'result' => 2,
            ],
            [
                'function' => 'is_array',
                'arguments' => [123],
                'result' => true
            ],
            [
                'function' => 'rand',
                'arguments' => [6, 10],
                'result' => 7,
            ],
        ]);

        $this->assertEquals(2, rand(1, 5));
        $this->assertTrue(is_array(123));
        $this->assertEquals(7, rand(6, 10));
    }
}