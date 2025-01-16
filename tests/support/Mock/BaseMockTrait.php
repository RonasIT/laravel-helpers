<?php

namespace RonasIT\Support\Tests\Support\Mock;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use RonasIT\Support\Traits\MockTrait;
use Symfony\Component\HttpFoundation\Response;

trait BaseMockTrait
{
    use MockTrait;

    protected function mockHttpRequestService(array $requests): void
    {
        $this->mockClass(Client::class, $requests);
    }

    protected function request(
        string $type,
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        array $responseData = [],
        int $statusCode = Response::HTTP_OK,
        ?string $dataOptionName = null,
    ): array {
        if (empty($dataOptionName)) {
            $dataOptionName = (in_array($type, ['get', 'delete'])) ? 'query' : 'json';
        }

        if ($dataOptionName === 'body') {
            $data = json_encode($data);
        }

        $request = [
            'headers' => $headers,
            'cookies' => null,
            'allow_redirects' => true,
            'connect_timeout' => 0,
        ];

        $request[$dataOptionName] = $data;

        return [
            'function' => 'request',
            'arguments' => [
                strtoupper($type),
                $url,
                array_merge($request, $options),
            ],
            'result' => new GuzzleResponse($statusCode, [], json_encode($responseData)),
        ];
    }
}
