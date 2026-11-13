<?php

namespace RonasIT\Support\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use RonasIT\Support\Exceptions\InvalidJSONFormatException;
use RonasIT\Support\Exceptions\UnknownRequestMethodException;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Tests\Support\Traits\HttpRequestServiceMockTrait;
use RuntimeException;

class HttpRequestServiceTest extends TestCase
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

        $this->responseProperty = new ReflectionProperty(HttpRequestService::class, 'response');
    }

    public function testSend()
    {
        $this->mockClass(HttpRequestService::class, [
            $this->functionCall(
                name: 'sendRequest',
                arguments: [
                    'get',
                    'https://some.url.com',
                    ['some_key' => 'some_value'],
                    ['some_header_name' => 'some_header_value'],
                ],
                result: new GuzzleResponse(200, [], json_encode([])),
            ),
        ]);

        app(HttpRequestService::class)->get(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: [
                'some_header_name' => 'some_header_value',
            ],
        );
    }

    public function testSetOption()
    {
        $this->httpRequestServiceClass->set('allow_redirects', false);

        $actualOptions = $this->optionsProperty->getValue($this->httpRequestServiceClass);

        $this->assertEquals(['allow_redirects' => false], $actualOptions);
    }

    public function testAllowRedirects()
    {
        $this->httpRequestServiceClass->allowRedirects(false);

        $this->mockGuzzleClient(
            method: 'put',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => false,
                    'connect_timeout' => 0,
                    'body' => '{"some_key":"some_value"}',
                ],
            ],
        );

        $this->httpRequestServiceClass->put(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: [
                'some_header' => 'some_header_value',
            ],
        );
    }

    public function testSaveCookieSession()
    {
        $this->httpRequestServiceClass->saveCookieSession();

        $this->mockGuzzleClient(
            method: 'get',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [],
                    'cookies' => new CookieJar(),
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                ],
            ],
        );

        $this->httpRequestServiceClass->get('https://some.url.com');
    }

    public function testConnectTimeout()
    {
        $this->httpRequestServiceClass->setConnectTimeout(999);

        $this->mockGuzzleClient(
            method: 'post',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 999,
                    'body' => '{"some_key":"some_value"}',
                ],
            ],
        );

        $this->httpRequestServiceClass->post(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: [
                'some_header' => 'some_header_value',
            ],
        );
    }

    public function testSendPutWithoutContentType()
    {
        $this->mockGuzzleClient(
            method: 'put',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                    'body' => '{"some_key":"some_value"}',
                ],
            ],
        );

        $this->httpRequestServiceClass->put(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: [
                'some_header' => 'some_header_value',
            ],
        );
    }

    public function testSendPutWithMultipartContentType()
    {
        $this->mockGuzzleClient(
            method: 'put',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                        'content-type' => 'application/x-www-form-urlencoded',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                    'form_params' => [
                        'some_key' => 'some_value',
                    ],
                ],
            ],
        );

        $this->httpRequestServiceClass->put(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: [
                'some_header' => 'some_header_value',
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
        );
    }

    public function testSendPutMultipartContentTypeWithFiles(): void
    {
        $this->mockGuzzleClient('post', [
            'https://some.url.com',
            [
                'headers' => [],
                'cookies' => null,
                'allow_redirects' => true,
                'connect_timeout' => 0,
                'multipart' => [
                    [
                        'name' => '0[first_file]',
                        'contents' => 'first_file_content',
                    ],
                    [
                        'name' => '0[second_file][first_file]',
                        'contents' => 'first_file_content',
                    ],
                    [
                        'name' => '0[second_file][second_file]',
                        'contents' => 'second_file_content',
                    ],
                    [
                        'name' => '1[first_file]',
                        'contents' => 'first_file_content',
                    ],
                    [
                        'name' => '1[second_file]',
                        'contents' => 'second_file_content',
                    ],
                ],
            ],
        ]);

        $this->httpRequestServiceClass->post('https://some.url.com', [
            [
                'first_file' => 'first_file_content',
                'second_file' => [
                    'first_file' => 'first_file_content',
                    'second_file' => 'second_file_content',
                ],
            ],
            [
                'first_file' => 'first_file_content',
                'second_file' => 'second_file_content',
            ],
        ], [
            'Content-type' => 'multipart/form-data',
        ]);
    }

    public function testParseMultipartContent(): void
    {
        $multipartContent = $this->getFixture('multipart_content');

        $this->mockGuzzleClient(
            method: 'get',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                ],
            ],
            response: new GuzzleResponse(
                200,
                ['Content-Type' => 'multipart/form-data; boundary=----------------------------83ff53821b7c'],
                $multipartContent,
            ),
        );

        $this->httpRequestServiceClass->get(url: 'https://some.url.com');

        $multipartObject = $this->httpRequestServiceClass->multipart();

        $parsedData = [];

        foreach ($multipartObject as $part) {
            $parsedData[] = [$part->getName(), $part->getBody()];
        }

        $this->assertEqualsFixture('parsed_multipart_content.json', $parsedData);
    }

    public static function sendPutAsJSONData(): array
    {
        return [
            [
                'headers' => ['content-type' => 'application/json'],
            ],
            [
                'headers' => ['Content-Type' => 'application/json'],
            ],
            [
                'headers' => ['CONTENT-TYPE' => 'application/json'],
            ],
            [
                'headers' => ['Content-type' => 'application/json'],
            ],
            [
                'headers' => ['CoNtEnT-TyPe' => 'application/json'],
            ],
        ];
    }

    #[DataProvider('sendPutAsJSONData')]
    public function testSendPutAsJSON(array $headers)
    {
        $this->mockGuzzleClient(
            method: 'put',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => ['content-type' => 'application/json'],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                    'json' => [
                        'some_key' => 'some_value',
                    ],
                ],
            ],
        );

        $this->httpRequestServiceClass->put(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: $headers,
        );
    }

    public function testSendDelete()
    {
        $this->mockGuzzleClient(
            method: 'delete',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                ],
            ],
        );

        $this->httpRequestServiceClass->delete('https://some.url.com', [
            'some_header' => 'some_header_value',
        ]);
    }

    public function testSendPatch()
    {
        $this->mockGuzzleClient(
            method: 'patch',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                    'body' => '{"some_key":"some_value"}',
                ],
            ],
        );

        $this->httpRequestServiceClass->patch(
            url: 'https://some.url.com',
            data: [
                'some_key' => 'some_value',
            ],
            headers: [
                'some_header' => 'some_header_value',
            ],
        );
    }

    public function testSendWithUnsupportedMethod()
    {
        $this->expectException(UnknownRequestMethodException::class);
        $this->expectExceptionMessageContains("Unknown request method 'unsupported'");

        $this->httpRequestServiceClass->send('unsupported', 'https://some.url.com');
    }

    public function testJSONResponse()
    {
        $responseJson = $this->getFixture('json_response.json');

        $this->mockGuzzleClient(
            method: 'get',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                    'query' => ['user' => 'admin'],
                ],
            ],
            response: new GuzzleResponse(200, [], $responseJson),
        );

        $this->httpRequestServiceClass->get(
            url: 'https://some.url.com',
            data: [
                'user' => 'admin',
            ],
            headers: [
                'some_header' => 'some_header_value',
            ],
        );

        $resultRaw = $this->httpRequestServiceClass->getResponse()->getBody();
        $resultJson = $this->httpRequestServiceClass->json();

        $this->assertEquals($responseJson, $resultRaw);
        $this->assertEquals(json_decode($responseJson, true), $resultJson);
    }

    public function testNotJSONResponse()
    {
        $this->expectException(InvalidJSONFormatException::class);

        $this->mockGuzzleClient(
            method: 'get',
            arguments: [
                'https://some.url.com',
                [
                    'headers' => [
                        'some_header' => 'some_header_value',
                    ],
                    'cookies' => null,
                    'allow_redirects' => true,
                    'connect_timeout' => 0,
                ],
            ],
            response: new GuzzleResponse(200, [], 'Some not json string'),
        );

        $this->httpRequestServiceClass->get(
            url: 'https://some.url.com',
            headers: [
                'some_header' => 'some_header_value',
            ],
        );

        $this->httpRequestServiceClass->json();
    }

    public function testSendWithRequestException()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessageContains('Some exception message');

        $mock = $this->createPartialMock(HttpRequestService::class, ['sendRequest']);
        $mock
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(
                fn () => throw new RequestException('Some exception message', new Request('type', 'url')),
            );

        $this->app->instance(HttpRequestService::class, $mock);

        app(HttpRequestService::class)->get('https://some.url.com');
    }

    public function testSendWithClientExceptionKeepsResponse()
    {
        $mock = $this->createPartialMock(HttpRequestService::class, ['sendRequest']);
        $mock
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(fn () => throw new ClientException(
                message: 'Some exception message',
                request: new Request('get', 'https://some.url.com'),
                response: new GuzzleResponse(422, [], '{"message":"Some error"}'),
            ));

        try {
            $mock->get('https://some.url.com');

            $this->fail('ClientException was not thrown');
        } catch (ClientException) {
            $this->assertEquals(422, $mock->getResponse()->getStatusCode());
            $this->assertEquals(['message' => 'Some error'], $mock->json());
        }
    }

    public function testSendWithNonStringHeaderValues()
    {
        $history = [];

        $handler = HandlerStack::create(new MockHandler([new GuzzleResponse(200)]));
        $handler->push(Middleware::history($history));

        $this->app->instance(Client::class, new Client(['handler' => $handler]));

        $this->httpRequestServiceClass->get(
            url: 'https://some.url.com',
            headers: [
                'X-Version' => 1,
                'X-Ratio' => 1.5,
                'X-Debug' => true,
                'Authorization' => null,
                'X-Ids' => [1, 2],
                'Accept' => ['application/json', 'text/plain'],
            ],
        );

        $request = $history[0]['request'];

        $this->assertSame(['1'], $request->getHeader('x-version'));
        $this->assertSame(['1.5'], $request->getHeader('x-ratio'));
        $this->assertSame(['1'], $request->getHeader('x-debug'));
        $this->assertSame([''], $request->getHeader('authorization'));
        $this->assertSame(['1', '2'], $request->getHeader('x-ids'));
        $this->assertSame(['application/json', 'text/plain'], $request->getHeader('accept'));
    }

    public function testSendWithRequestExceptionInDebugMode()
    {
        config(['defaults.http_service_debug' => true]);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessageContains('Some exception message');

        $handler = HandlerStack::create(new MockHandler([
            new RequestException('Some exception message', new Request('get', 'https://some.url.com')),
        ]));

        $this->app->instance(Client::class, new Client(['handler' => $handler]));

        (new HttpRequestService())->get('https://some.url.com');
    }

    public function testSendResetsPreviousResponseOnFailure()
    {
        $handler = HandlerStack::create(new MockHandler([
            new GuzzleResponse(200, [], '{"key":"value"}'),
            new RuntimeException('Connection failed'),
        ]));

        $this->app->instance(Client::class, new Client(['handler' => $handler]));

        $this->httpRequestServiceClass->get('https://some.url.com');

        try {
            $this->httpRequestServiceClass->get('https://some.url.com');

            $this->fail('RuntimeException was not thrown');
        } catch (RuntimeException) {
            $this->assertNull($this->httpRequestServiceClass->getResponse());
        }
    }

    public function testJSONWithoutResponse()
    {
        $this->expectException(InvalidJSONFormatException::class);

        $this->httpRequestServiceClass->json();
    }
}
