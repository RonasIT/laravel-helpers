<?php

namespace RonasIT\Support\Tests\Support\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

trait HttpRequestServiceMockTrait
{
    use MockTrait;

    protected function mockGuzzleClient($method, $arguments, $response = null)
    {
        $mock = $this->mockClass(Client::class, [$method]);

        $mock
            ->expects($this->exactly(1))
            ->method($method)
            ->withConsecutive($arguments)
            ->willReturn(
                $response ?? new GuzzleResponse(200, [])
            );

        $this->app->instance(Client::class, $mock);
    }
}
