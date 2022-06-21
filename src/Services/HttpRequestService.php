<?php

namespace RonasIT\Support\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use RonasIT\Support\Exceptions\UnknownRequestMethodException;

class HttpRequestService
{
    protected $debug;

    protected $connectTimeout = 0;
    protected $allowRedirects = true;

    protected $options = [];
    protected $cookies = null;

    protected $response;

    public function __construct()
    {
        $this->debug = config('defaults.http_service_debug', false);
    }

    public function set(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function json(): array
    {
        $stringResponse = (string) $this->response->getBody();

        return json_decode($stringResponse, true);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function saveCookieSession(): self
    {
        $this->cookies = app(CookieJar::class);

        return $this;
    }

    public function getCookie(): array
    {
        if (empty($this->cookies)) {
            return [];
        }

        return $this->cookies->toArray();
    }

    public function allowRedirects(bool $value = true): self
    {
        $this->allowRedirects = $value;

        return $this;
    }

    public function setConnectTimeout(int $seconds = 0): self
    {
        $this->connectTimeout = $seconds;

        return $this;
    }

    public function get(string $url, array $data = [], array $headers = []): self
    {
        return $this->send('get', $url, $data, $headers);
    }

    public function post(string $url, array $data, array $headers = []): self
    {
        return $this->send('post', $url, $data, $headers);
    }

    public function delete(string $url, array $headers = []): self
    {
        return $this->send('delete', $url, [], $headers);
    }

    public function put(string $url, array $data, array $headers = []): self
    {
        return $this->send('put', $url, $data, $headers);
    }

    public function patch(string $url, array $data, array $headers = []): self
    {
        return $this->send('patch', $url, $data, $headers);
    }

    protected function send(string $method, string $url, array $data = [], array $headers = []): self
    {
        $time = microtime(true);

        $this->logRequest($method, $url, $data, $headers);
        $this->setOptions($headers);
        $this->setData($method, $headers, $data);

        try {
            $this->response = $this->sendRequest($method, $url);

            return $this;
        } catch (RequestException $exception) {
            $this->response = $exception->getResponse();

            throw $exception;
        } finally {
            $this->logResponse($this->response, $time);
            $this->options = [];
        }
    }

    protected function sendRequest($method, $url): ResponseInterface
    {
        $client = new Client();

        switch ($method) {
            case 'get':
                $response = $client->get($url, $this->options);
                break;
            case 'post':
                $response = $client->post($url, $this->options);
                break;
            case 'put':
                $response = $client->put($url, $this->options);
                break;
            case 'patch':
                $response = $client->patch($url, $this->options);
                break;
            case 'delete':
                $response = $client->delete($url, $this->options);
                break;
            default :
                throw app(UnknownRequestMethodException::class)->setMethod($method);
        }

        return $response;
    }

    protected function logRequest(string $typeOfRequest, string $url, array $data, array $headers): void
    {
        if ($this->debug) {
            logger('');
            logger('-------------------------------------');
            logger('');
            logger("sending {$typeOfRequest} request:", [
                'url' => $url,
                'data' => $data,
                'headers' => $headers
            ]);
            logger('');
        }
    }

    protected function logResponse(ResponseInterface $response, ?int $time = null): void
    {
        if ($this->debug) {
            logger('');
            logger('-------------------------------------');
            logger('');
            logger('getting response: ');
            logger('code', ["<{$response->getStatusCode()}>"]);
            logger('body', ["<{$response->getBody()}>"]);
            logger('time', [!empty($time) ? (microtime(true) - $time) : null]);
            logger('');
        }
    }

    private function setOptions(array $headers): self
    {
        $this->options['headers'] = $headers;
        $this->options['cookies'] = $this->cookies;
        $this->options['allow_redirects'] = $this->allowRedirects;
        $this->options['connect_timeout'] = $this->connectTimeout;

        return $this;
    }

    private function setData(string $method, array $headers, array $data = []): void
    {
        if (empty($data)) {
            return;
        }

        if ($method == 'get') {
            $this->options['query'] = $data;

            return;
        }

        $contentType = elseChain(
            function () use ($headers) {
                return Arr::get($headers, 'Content-Type');
            },
            function () use ($headers) {
                return Arr::get($headers, 'content-type');
            },
            function () use ($headers) {
                return Arr::get($headers, 'CONTENT-TYPE');
            }
        );

        if (preg_match('/application\/json/', $contentType)) {
            $this->options['json'] = $data;

            return;
        }

        $this->options['form_params'] = $data;
    }
}
