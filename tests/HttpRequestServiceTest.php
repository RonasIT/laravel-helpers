<?php

namespace RonasIT\Support\Tests;

use ReflectionProperty;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Exceptions\InvalidJSONFormatException;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use RonasIT\Support\Tests\Support\Traits\HttpRequestServiceMockTrait;

class HttpRequestServiceTest extends HelpersTestCase
{
    use MockTrait, HttpRequestServiceMockTrait;

    protected HttpRequestService $httpRequestServiceClass;
    protected ReflectionProperty $optionsProperty;
    protected ReflectionProperty $responseProperty;

    public function setUp(): void
    {
        parent::setUp();

        $this->httpRequestServiceClass = new HttpRequestService();

        $this->optionsProperty = new ReflectionProperty(HttpRequestService::class, 'options');
        $this->optionsProperty->setAccessible(true);

        $this->responseProperty = new ReflectionProperty(HttpRequestService::class, 'response');
        $this->responseProperty->setAccessible(true);
    }

    public function testSend()
    {
        $mock = $this->mockClass(HttpRequestService::class, ['sendRequest']);

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

    public function testSetOption()
    {
        $this->httpRequestServiceClass->set('allow_redirects', true);

        $actualOptions = $this->optionsProperty->getValue($this->httpRequestServiceClass);

        $this->assertEquals([
            'allow_redirects' => true
        ], $actualOptions);
    }

    public function testSendPut()
    {
        $this->mockGuzzleClient('put', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0,
                'form_params' => [
                    'some_key' => 'some_value'
                ]
            ]
        ]);

        $this->httpRequestServiceClass->put('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header' => 'some_header_value'
        ]);
    }

    public function sendPutAsJSONData(): array
    {
        return [
            [
                'headers' => ['content-type' => 'application/json']
            ],
            [
                'headers' => ['Content-Type' => 'application/json']
            ],
            [
                'headers' => ['CONTENT-TYPE' => 'application/json']
            ],
            [
                'headers' => ['Content-type' => 'application/json']
            ],
            [
                'headers' => ['CoNtEnT-TyPe' => 'application/json']
            ],
        ];
    }

    /**
     * @dataProvider sendPutAsJSONData
     *
     * @param array $headers
     */
    public function testSendPutAsJSON(array $headers)
    {
        $this->mockGuzzleClient('put', [
            'https://some.url.com',
            [
                'headers' => $headers,
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0,
                'json' => [
                    'some_key' => 'some_value'
                ]
            ]
        ]);

        $this->httpRequestServiceClass->put('https://some.url.com', [
            'some_key' => 'some_value'
        ], $headers);
    }

    public function testJSONResponse()
    {
        $response = $this->getFixture('json_response.json');

        $this->responseProperty->setValue($this->httpRequestServiceClass, new GuzzleResponse(200, [], $response));

        $result = $this->httpRequestServiceClass->json();

        $this->assertEquals(json_decode($response, true), $result);
    }

    public function testNotJSONResponse()
    {
        $this->expectException(InvalidJSONFormatException::class);

        $this->responseProperty->setValue($this->httpRequestServiceClass, new GuzzleResponse(401, [], 'Some not json string'));

        $this->httpRequestServiceClass->json();
    }
}
