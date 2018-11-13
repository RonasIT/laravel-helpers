<?php

namespace RonasIT\Support\Services;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;
use RonasIT\Support\Exceptions\UnknownRequestMethodException;

class HttpRequestService
{
    protected $debug;

    protected $allowRedirects = true;

    protected $options = [];
    protected $cookies = null;

    public function __construct()
    {
        $this->debug = config('defaults.http_service_debug', false);
    }

    public function set($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function sendGet($url, $data = null, $headers = [])
    {
        return $this->send('get', $url, $data, $headers);
    }

    public function sendPost($url, $data, $headers = [])
    {
        return $this->send('post', $url, $data, $headers);
    }

    public function sendDelete($url, $headers = [])
    {
        return $this->send('delete', $url, $headers);
    }

    public function sendPut($url, $data, $headers = [])
    {
        return $this->send('put', $url, $data, $headers);
    }

    protected function send($method, $url, $data = [], $headers = [])
    {
        $client = new Client();

        $time = microtime(true);

        $this->logRequest($method, $url, $data);
        $this->setOptions($headers);
        $this->setData($method, $headers, $data);

        switch ($method) {
            case 'get' :
                $response = $client->get($url, $this->options);
                break;
            case 'post' :
                $response = $client->post($url, $this->options);
                break;
            case 'put' :
                $response = $client->put($url, $this->options);
                break;
            case 'delete' :
                $response = $client->delete($url, $this->options)->send();
                break;
            default :
                throw app(UnknownRequestMethodException::class)->setMethod($method);
        }

        $this->logResponse($response, $time);

        return $response;
    }

    protected function logRequest($typeOfRequest, $url, $data)
    {
        if ($this->debug) {
            logger('');
            logger('-------------------------------------');
            logger('');
            logger("sending {$typeOfRequest} request:", [
                'url' => $url,
                'data' => $data
            ]);
            logger('');
        }
    }

    protected function logResponse($response, $time = null)
    {
        if ($this->debug) {
            logger('');
            logger('-------------------------------------');
            logger('');
            logger('getting response: ');
            logger('code', ["<{$response->getStatusCode()}>"]);
            logger('body', ["<{$response->getBody(true)}>"]);
            logger('time', [!empty($time) ? (microtime(true) - $time) : null]);
            logger('');
        }
    }

    public function parseJsonResponse($response)
    {
        $stringResponse = (string)$response->getBody();

        return json_decode($stringResponse, true);
    }

    public function saveCookieSession()
    {
        $this->cookies = app(CookieJar::class);

        return $this;
    }

    public function getCookie()
    {
        if (empty($this->cookies)) {
            return [];
        }

        return $this->cookies->toArray();
    }

    public function disallowRedirects()
    {
        $this->allowRedirects = false;

        return $this;
    }

    private function setOptions($headers)
    {
        $this->options = [];

        $this->options['headers'] = $headers;
        $this->options['cookies'] = $this->cookies;
        $this->options['allow_redirects'] = $this->allowRedirects;
    }

    private function setData($method, $headers, $data = [])
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
                return array_get($headers, 'Content-Type');
            },
            function () use ($headers) {
                return array_get($headers, 'content-type');
            },
            function () use ($headers) {
                return array_get($headers, 'CONTENT-TYPE');
            }
        );

        if (preg_match('/application\/json/', $contentType)) {
            $this->options['json'] = $data;
            return;
        }

        $this->options['form_params'] = $data;
    }
}