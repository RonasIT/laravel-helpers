<?php

namespace RonasIT\Support\Tests;

use ReflectionProperty;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Exceptions\InvalidJSONFormatException;
use RonasIT\Support\Exceptions\UnknownRequestMethodException;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Tests\Support\Traits\HttpRequestServiceMockTrait;

class HttpRequestServiceTest extends HelpersTestCase
{
    use HttpRequestServiceMockTrait;

    protected HttpRequestService $httpRequestServiceClass;
    protected ReflectionProperty $optionsProperty;
    protected ReflectionProperty $responseProperty;
    protected ReflectionProperty $allowRedirectsProperty;
    protected ReflectionProperty $connectTimeoutProperty;

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
        $this->mockClass(HttpRequestService::class, [
            $this->functionCall(
                'sendRequest',
                [
                    'get',
                    'https://some.url.com',
                    ['some_key' => 'some_value'],
                    ['some_header_name' => 'some_header_value']
                ],
                new GuzzleResponse(200, [], json_encode([]))
            ),
        ]);

        app(HttpRequestService::class)->get('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header_name' => 'some_header_value'
        ]);
    }

    public function testSetOption()
    {
        $this->httpRequestServiceClass->set('allow_redirects', false);

        $actualOptions = $this->optionsProperty->getValue($this->httpRequestServiceClass);

        $this->assertEquals([
            'allow_redirects' => false
        ], $actualOptions);
    }

    public function testAllowRedirects()
    {
        $this->httpRequestServiceClass->allowRedirects(false);

        $this->mockGuzzleClient('put', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => false,
                'connect_timeout' => 0,
                'body' => '{"some_key":"some_value"}'
            ]
        ]);

        $this->httpRequestServiceClass->put('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header' => 'some_header_value'
        ]);
    }

    public function testConnectTimeout()
    {
        $this->httpRequestServiceClass->setConnectTimeout(999);

        $this->mockGuzzleClient('post', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 999,
                'body' => '{"some_key":"some_value"}'
            ]
        ]);

        $this->httpRequestServiceClass->post('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header' => 'some_header_value'
        ]);
    }

    public function testSendPutWithoutContentType()
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
                'body' => '{"some_key":"some_value"}'
            ]
        ]);

        $this->httpRequestServiceClass->put('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header' => 'some_header_value'
        ]);
    }

    public function testSendPutWithMultipartContentType()
    {
        $this->mockGuzzleClient('put', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value',
                    'Content-type' => 'application/x-www-form-urlencoded'
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
            'some_header' => 'some_header_value',
            'Content-type' => 'application/x-www-form-urlencoded'
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

    public function testSendDelete()
    {
        $this->mockGuzzleClient('delete', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0
            ]
        ]);

        $this->httpRequestServiceClass->delete('https://some.url.com', [
            'some_header' => 'some_header_value'
        ]);
    }

    public function testSendPatch()
    {
        $this->mockGuzzleClient('patch', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0,
                'body' => '{"some_key":"some_value"}'
            ]
        ]);

        $this->httpRequestServiceClass->patch('https://some.url.com', [
            'some_key' => 'some_value'
        ], [
            'some_header' => 'some_header_value'
        ]);
    }

    public function testSendWithUnsupportedMethod()
    {
        $this->expectException(UnknownRequestMethodException::class);
        $this->expectExceptionMessage("Unknown request method 'unsupported'");

        $this->httpRequestServiceClass->send('unsupported', 'https://some.url.com');
    }

    public function testJSONResponse()
    {
        $responseJson = $this->getFixture('json_response.json');

        $this->mockGuzzleClient('get', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0
            ]
        ], new GuzzleResponse(200, [], $responseJson));

        $this->httpRequestServiceClass->get('https://some.url.com', [], [
            'some_header' => 'some_header_value'
        ]);

        $resultRaw = $this->httpRequestServiceClass->getResponse()->getBody();
        $resultJson = $this->httpRequestServiceClass->json();

        $this->assertEquals($responseJson, $resultRaw);
        $this->assertEquals(json_decode($responseJson, true), $resultJson);
    }

    public function testNotJSONResponse()
    {
        $this->expectException(InvalidJSONFormatException::class);

        $this->mockGuzzleClient('get', [
            'https://some.url.com',
            [
                'headers' => [
                    'some_header' => 'some_header_value'
                ],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0
            ]
        ], new GuzzleResponse(200, [], 'Some not json string'));

        $this->httpRequestServiceClass->get('https://some.url.com', [], [
            'some_header' => 'some_header_value'
        ]);

        $this->httpRequestServiceClass->json();
    }
}
