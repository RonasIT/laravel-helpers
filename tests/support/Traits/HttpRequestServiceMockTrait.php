<?php

namespace RonasIT\Support\Tests\Support\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Traits\MockTrait;

trait HttpRequestServiceMockTrait
{
    use MockTrait;

    protected function mockGuzzleClient($method, $arguments, $response = null): void
    {
        $this->mockClass(Client::class, [
            $this->methodCall($method, $arguments, $response ?? new GuzzleResponse(200, [])),
        ]);
    }
}
