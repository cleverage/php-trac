<?php

namespace CleverAge\Trac\HttpClient\Guzzle;

use CleverAge\Trac\HttpClient\AbstractHttpClient;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

use CleverAge\Trac\Exception;

class GuzzleHttpClient extends AbstractHttpClient
{
    /**
     * @var Guzzle\Http\Client
     */
    protected $client;

    /**
     * @var int
     */
    protected $parallelLimit = 5;

    public function __construct(Client $client = null)
    {
        if (!$client) {
            $client = new Client();
        }

        $this->client = $client;
    }

    /**
     * @param int $limit
     * @return \CleverAge\Trac\HttpClient\Guzzle\GuzzleHttpClient
     */
    public function setParallelLimit($limit)
    {
        $this->parallelLimit = (int) $limit;

        return $this;
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

    public function doManyRequests(array $requests, array $headers = array())
    {
        $totalToDo = count($requests);
        $responses = array();

        try {
            while($totalToDo > 0) {
                $clientRequests = array();
                for($i=0; $i < $this->parallelLimit && $totalToDo > 0; $i++, $totalToDo--) {
                    $clientRequests[] = $this->client->post('', $headers, json_encode(array_shift($requests)));
                }
                $responses = array_merge($responses, $this->client->send($clientRequests));
            }
        } catch (\Exception $e) {
            throw new Exception('Error in request : '.$e->getMessage());
        }

        $results = array();

        foreach ($responses as $k => $response) {
            $results[$k] = $this->parseResponse($response);
        }

        return $results;
    }
}
