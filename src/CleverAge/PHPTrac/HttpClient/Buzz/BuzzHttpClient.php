<?php

namespace CleverAge\PHPTrac\HttpClient\Buzz;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\MessageInterface;

use CleverAge\PHPTrac\Exception;
use CleverAge\PHPTrac\HttpClient\AbstractHttpClient;

class BuzzHttpClient extends AbstractHttpClient
{
    /**
     * @var Buzz\Browser
     */
    protected $browser;

    /**
     * @var string
     */
    protected $url;

    public function __construct(Browser $browser = null)
    {
        if (!$browser) {
            $buzzClient = new Curl();
            $browser = new Browser($buzzClient);
        }

        $this->browser = $browser;
    }

    public function setBaseUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    protected function getArrayResultFromResponse($response)
    {
        return json_decode($response->getContent(), true);
    }

    protected function post($params, array $headers = array())
    {
        return $this->browser->post($this->url, $headers, $params);
    }

    protected function parseResponse($response)
    {
        if (!$response instanceof MessageInterface) {
            throw new Exception('Invalid response');
        }

        $headers = $response->getHeaders();
        if (false === strpos($headers[0], '200')) {
           throw new Exception('Incorrect response : '.$headers[0]);
        }

        return parent::parseResponse($response);
    }
}
