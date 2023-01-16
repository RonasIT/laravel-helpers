<?php

namespace RonasIT\Support\Traits;

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
     *     ]
     * ]
     *
     * @param string $class
     * @param array $callChain
     */
    public function mockClass(string $class, array $callChain): void
    {
        $methodsCalls = collect($callChain)->groupBy('method');

        $mock = $this
            ->getMockBuilder($class)
            ->onlyMethods($methodsCalls->keys()->toArray())
            ->getMock();

        $methodsCalls->each(function ($calls, $method) use (&$mock) {
            $mock
                ->expects($this->exactly($calls->count()))
                ->method($method)
                ->withConsecutive(...$calls->map(function ($call) {
                    return $call['arguments'];
                })->toArray())
                ->willReturnOnConsecutiveCalls(...$calls->map(function ($call) {
                    return $call['result'];
                })->toArray());
        });

        $this->app->instance($class, $mock);
    }
}
