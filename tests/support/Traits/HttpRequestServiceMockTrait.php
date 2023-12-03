<?php

namespace RonasIT\Support\Tests\Support\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Traits\WithConsecutiveTrait;

trait HttpRequestServiceMockTrait
{
    use WithConsecutiveTrait;
    use MockTrait;

    protected function mockGuzzleClient($method, $arguments, $response = null): void
    {
        $mock = $this->mockClass(Client::class, [$method]);

        $mock
            ->expects($this->exactly(1))
            ->method($method)
            ->with(...$this->withConsecutive($arguments))
            ->willReturn(
                $response ?? new GuzzleResponse(200, [])
            );

        $this->app->instance(Client::class, $mock);
    }
}
