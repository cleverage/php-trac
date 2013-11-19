<?php

namespace CleverAge\PHPTrac\HttpClient\Guzzle;

use CleverAge\PHPTrac\HttpClient\AbstractHttpClient;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

use CleverAge\PHPTrac\Exception;

class GuzzleHttpClient extends AbstractHttpClient
{
    /**
     * @var Guzzle\Http\Client
     */
    protected $client;

    public function __construct(Client $client = null)
    {
        if (!$client) {
            $client = new Client();
        }

        $this->client = $client;
    }

    public function setBaseUrl($url)
    {
        $this->client->setBaseUrl($url);

        return $this;
    }

    protected function post($params, array $headers = array())
    {
        $request = $this->client->post('', $headers, $params);
        return $request->send();
    }

    protected function getArrayResultFromResponse($response)
    {
        if (!$response instanceof Response) {
            throw new Exception('Invalid response');
        }

        return $response->json();
    }
}
