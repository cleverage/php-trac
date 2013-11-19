<?php

namespace CleverAge\PHPTrac\HttpClient;

use CleverAge\PHPTrac\Exception;

abstract class AbstractHttpClient implements HttpClientInterface
{
    public function doRequest(array $params = array(), array $headers = array())
    {
        try {
            $response = $this->post(json_encode($params), $headers);

        } catch (\Exception $e) {

            throw new Exception('Error in Trac request : '.$e->getMessage());
        }

        return $this->parseResponse($response);
    }

    abstract protected function post($params, array $headers = array());

    abstract protected function getArrayResultFromResponse($response);

    protected function parseResponse($response)
    {
        $error = null;

        $result = $this->getArrayResultFromResponse($response);

        if (!is_array($result) || !array_key_exists('result', $result)) {
            $error = $result;
        } elseif (array_key_exists('error', $result) && !empty($result['error'])) {
            $error = $result['error']['code'].' : '.$result['error']['message'];
        }

        if (!is_null($error)) {
            throw new Exception('Incorrect response : '.$error);
        }

        return $result['result'];
    }
}
