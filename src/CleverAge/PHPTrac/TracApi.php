<?php

namespace CleverAge\PHPTrac;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\RequestInterface;
use Buzz\Message\MessageInterface;

class TracApi
{
    const AUTH_NONE = 0;
    const AUTH_BASIC = 1;

    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);

        $this->config = $resolver->resolve($config);
    }

    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(array(
                'url', 'ticket.class', 'auth'
            ))
            ->setOptional(array(
                'user.login', 'user.password'
            ))
            ->setDefaults(array(
                'auth' => self::AUTH_NONE,
                'ticket.class' => 'CleverAge\PHPTrac\Ticket'
            ))
        ;
    }

    protected function doRequest($method, array $params = [], $type = RequestInterface::METHOD_POST)
    {
        $queryParams = [
            'params' => $params,
            'method' => $method,
        ];

        $buzzClient = new Curl();
        $browser = new Browser($buzzClient);

        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );

        if ($this->config['auth'] == self::AUTH_BASIC) {

            if (!array_key_exists('user.login', $this->config) || !array_key_exists('user.password', $this->config)) {
                throw new MissingOptionsException('user.login and user.password must be provided for Basic auth');
            }

            $headers['Authorization'] = 'Basic '.base64_encode($this->config['user.login'].':'.$this->config['user.password']);
        }

        $path = $this->config['auth'] == self::AUTH_NONE ? 'jsonrpc' : 'login/jsonrpc';
        $url = $this->config['url'].$path;

        try {
            $response = $browser->call($url, $type, $headers, json_encode($queryParams));

        } catch (\Exception $e) {
            throw new Exception('Error in Trac request : '.$e->getMessage());
        }

        return $this->parseResponse($response);
    }

    protected function parseResponse(MessageInterface $response)
    {
        $error = null;

        $headers = $response->getHeaders();

        if (false === strpos($headers[0], '200')) {
            $error = $headers[0];
        } else {
            $result = json_decode($response->getContent(), true);

            if (!is_array($result) || !array_key_exists('result', $result)) {
                $error = $result;
            } elseif (array_key_exists('error', $result) && !empty($result['error'])) {
                $error = $result['error']['code'].' : '.$result['error']['message'];
            }
        }

        if (!is_null($error)) {
            throw new Exception('Incorrect response : '.$error);
        }

        return $result['result'];
    }

    public function getTicketListByStatus($status = '', $limit = 0)
    {
        $list = $this->doRequest('ticket.query', array('status='.$status.'&max='.$limit));

        $tickets = [];
        foreach($list as $id) {
            $tickets[] = $this->getTicketById((int) $id);
        }

        return $tickets;
    }

    public function getTicketById($id)
    {
        $arrayFromApi = $this->doRequest('ticket.get', array($id));
        $class = $this->config['ticket.class'];
        return new $class($this->config['url'], $arrayFromApi);
    }
}
