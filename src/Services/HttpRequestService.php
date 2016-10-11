<?php
/**
 * Created by PhpStorm.
 * User: ascet
 * Date: 12.07.15
 * Time: 17:23
 */

namespace RonasIT\Support\Services;

use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Log\Writer;
use GuzzleHttp\Client;
use App\Exceptions\UnknownRequestMethodException;

class HttpRequestService
{
    private $logger;

    public function __construct() {
        $this->logger = app(Writer::class);
    }

    public function sendGet($url, $data = null, $headers = null) {
        /* @var Client $client */

        $client = new Client();

        $this->logRequest('get', $url, $data);

        $time = microtime(true);

        try {
            if (!empty($data)) {
                $response = $client->get($url, [
                    'query' => $data,
                    'headers' => $headers
                ]);
            } else {
                $response = $client->get($url, [
                    'headers' => $headers
                ]);
            }
        } catch (BadResponseException $e) {
            $this->logResponse($e->getResponse(), $time);

            throw $e;
        }

        $this->logResponse($response);

        return $response;
    }

    public function sendPost($url, $data) {
        return $this->send('post', $url, $data);
    }

    public function sendDelete($url) {
        return $this->send('delete', $url);
    }

    public function sendPut($url, $data) {
        return $this->send('put', $url, $data);
    }

    protected function send($method, $url, $data = null) {
        $client = new Client($url);

        $time = microtime(true);

        $this->logRequest('put', $url, $data);

        switch ($method) {
            case 'post' :
                $response = $client->post($url, null, $data);
                break;
            case 'put' :
                $response = $client->put($url, null, $data);
                break;
            case 'delete' :
                $response = $client->delete($url)->send();
                break;
            default :
                throw app(UnknownRequestMethodException::class)->setMethod($method);
        }

        $this->logResponse($response, $time);

        return $response;
    }

    protected function logRequest($typeOfRequest, $url, $data) {
        if (config('app.debug')) {
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
        if (config('app.debug')) {
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
}