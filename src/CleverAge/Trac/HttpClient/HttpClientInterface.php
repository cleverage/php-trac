<?php

namespace CleverAge\Trac\HttpClient;

interface HttpClientInterface
{
    public function setBaseUrl($url);

    public function doRequest(array $params = array(), array $headers = array());

    public function doManyRequests(array $requests);
}
