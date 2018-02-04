<?php
/**
 * Created by PhpStorm.
 * User: ascet
 * Date: 12.07.15
 * Time: 17:23
 */

namespace RonasIT\Support\Services;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Log\Writer;
use GuzzleHttp\Client;
use RonasIT\Support\Exceptions\UnknownRequestMethodException;

class HttpRequestService
{
    private $logger;
    protected $debug;
    protected $cookies = null;

    public function __construct($debug = null) {
        if ($debug === null) {
            $this->debug = config('app.debug');
        }

        $this->logger = app(Writer::class);
    }

    public function sendGet($url, $data = null, $headers = null) {
        return $this->send('get', $url, $data, $headers);
    }

    public function sendPost($url, $data, $headers = null) {
        return $this->send('post', $url, $data, $headers);
    }

    public function sendDelete($url, $headers = null) {
        return $this->send('delete', $url, $headers);
    }

    public function sendPut($url, $data, $headers = null) {
        return $this->send('put', $url, $data, $headers);
    }

    protected function send($method, $url, $data = [], $headers = []) {
        $client = new Client();

        $time = microtime(true);

        $this->logRequest('put', $url, $data);
        $options = [
            'headers' => $headers,
            'cookies' => $this->cookies
        ];

        $this->setData($options, $method, $headers, $data);

        switch ($method) {
            case 'get' :
                $response = $client->get($url, $options);
                break;
            case 'post' :
                $response = $client->post($url, $options);
                break;
            case 'put' :
                $response = $client->put($url, $options);
                break;
            case 'delete' :
                $response = $client->delete($url, $options)->send();
                break;
            default :
                throw app(UnknownRequestMethodException::class)->setMethod($method);
        }

        $this->logResponse($response, $time);

        return $response;
    }

    protected function logRequest($typeOfRequest, $url, $data) {
        if ($this->debug) {
            $this->logger->info('');
            $this->logger->info('-------------------------------------');
            $this->logger->info('');
            $this->logger->info("sending {$typeOfRequest} request:", [
                'url' => $url,
                'data' => $data
            ]);
            $this->logger->info('');
        }
    }

    protected function logResponse($response, $time = null) {
        if ($this->debug) {
            $this->logger->info('');
            $this->logger->info('-------------------------------------');
            $this->logger->info('');
            $this->logger->info('getting response: ');
            $this->logger->info('code', ["<{$response->getStatusCode()}>"]);
            $this->logger->info('body', ["<{$response->getBody(true)}>"]);
            $this->logger->info('time', [!empty($time) ? (microtime(true) - $time) : null]);
            $this->logger->info('');
        }
    }

    public function parseJsonResponse($response) {
        $stringResponse = (string)$response->getBody();

        return json_decode($stringResponse, true);
    }

    public function saveCookieSession() {
        $this->cookies = app(CookieJar::class);

        return $this;
    }

    public function getCookie() {
        if (empty($this->cookies)) {
            return [];
        }

        return $this->cookies->toArray();
    }

    private function setData(&$options, $method, $headers, $data = []) {
        if (empty($data)) {
            return;
        }

        if ($method == 'get') {
            $options['query'] = $data;
            return;
        }

        $contentType = elseChain(
            function () use ($headers) { return $headers['Content-Type']; },
            function () use ($headers) { return $headers['content-type']; },
            function () use ($headers) { return $headers['CONTENT-TYPE']; }
        );

        if (preg_match('/application\/json/', $contentType)) {
            $options['json'] = $data;
            return;
        }

        $options['form_params'] = $data;
    }
}