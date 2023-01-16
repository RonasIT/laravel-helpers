<?php

namespace RonasIT\Support\Tests\Support\Traits;

use GuzzleHttp\Client;

trait HttpRequestServiceMockTrait
{
    use MockTrait;

    protected function mockGuzzleClient($method, $arguments)
    {
        $mock = $this->mockClass(Client::class, [$method]);

        $mock->expects($this->exactly(1))->method($method)->withConsecutive($arguments);

        $this->app->instance(Client::class, $mock);
    }
}
