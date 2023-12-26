<?php

namespace RonasIT\Support\Tests\Support\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Traits\MockClassTrait;

trait HttpRequestServiceMockTrait
{
    use MockClassTrait;

    protected function mockGuzzleClient($method, $arguments, $response = null): void
    {
        $this->mockClass(Client::class, [
            [
                'method' => $method,
                'arguments' => $arguments,
                'result' =>  $response ?? new GuzzleResponse(200, [])
            ]
        ]);
    }
}
