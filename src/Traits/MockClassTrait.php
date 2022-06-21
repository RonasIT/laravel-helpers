<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;

trait MockClassTrait
{
    /**
     * Mock selected class. Call chain should looks like:
     *
     * [
     *     [
     *         'method' => 'yourMethod',
     *         'result' => 'result_fixture.json'
     *     ]
     * ]
     *
     * @param string $class
     * @param array $callChain
     */
    public function mockClass(string $class, array $callChain): void
    {
        $methods = Arr::pluck($callChain, 'method');
        $mock = $this
            ->getMockBuilder($class)
            ->setMethods($methods)
            ->getMock();

        foreach ($callChain as $call) {
            $mock->method($call['method'])->willReturn($call['result']);
        }

        $this->app->instance($class, $mock);
    }
}
