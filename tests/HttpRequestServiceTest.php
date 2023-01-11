<?php

namespace RonasIT\Support\Tests;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Tests\Support\Traits\MockTrait;

class HttpRequestServiceTest extends HelpersTestCase
{
    use MockTrait;

    protected $httpRequestServiceClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->httpRequestServiceClass = new HttpRequestService();
    }

    public function testSend()
    {
        $mock = $this->mockCLass(HttpRequestService::class, ['sendRequest']);

        $mock
            ->expects($this->once())
            ->method('sendRequest')
            ->with('get', 'https://some.url.com', [
                'some_key' => 'some_value'
            ], [
                'some_header_name' => 'some_header_value'
            ])
            ->willReturn(new GuzzleResponse(200, [], json_encode([])));

        $mock->get('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header_name' => 'some_header_value'
        ]);
    }
}
