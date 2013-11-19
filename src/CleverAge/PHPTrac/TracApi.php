<?php

namespace CleverAge\PHPTrac;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

use CleverAge\PHPTrac\HttpClient\HttpClientInterface;

class TracApi
{
    const AUTH_NONE = 0;
    const AUTH_BASIC = 1;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var CleverAge\PHPTrac\HttpClient\HttpClientInterface
     */
    protected $client;

    // ------ INIT ------ \\

    public function __construct(array $config, HttpClientInterface $client)
    {
        $this->config = $this->resolveConfig($config);
        $this->client = $client;
        $this->client->setBaseUrl($this->config['url']);
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

    protected function resolveConfig(array $config)
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $config = $resolver->resolve($config);

        $config['headers'] = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );

        if ($config['auth'] == self::AUTH_BASIC) {

            if (!array_key_exists('user.login', $config) || !array_key_exists('user.password', $config)) {
                throw new MissingOptionsException('user.login and user.password must be provided for Basic auth');
            }

            $config['headers']['Authorization'] = 'Basic '.base64_encode($config['user.login'].':'.$config['user.password']);
        }

        $config['url'] .= $config['auth'] == self::AUTH_NONE ? 'jsonrpc' : 'login/jsonrpc';

        return $config;
    }

    // ------ HTTP ------ \\

    protected function doRequest($method, array $params = array(), array $headers = array())
    {
        $queryParams = $this->prepareQueryParams($method, $params);
        return $this->client->doRequest($queryParams, array_merge($this->config['headers'], $headers));
    }

    protected function prepareQueryParams($method, array $params = array())
    {
        return array(
            'params' => $params,
            'method' => $method,
        );
    }

    // ------ Public Functional Methods ------ \\

    public function getTicketIdsByStatus($status = '', $limit = 0)
    {
        return $this->doRequest('ticket.query', array('status='.$status.'&max='.$limit));
    }

    public function getTicketById($id)
    {
        $arrayFromApi = $this->doRequest('ticket.get', array($id));
        $class = $this->config['ticket.class'];
        return new $class($this->config['url'], $arrayFromApi);
    }

    // ------ Aggregations ------ \\

    public function getTicketListByStatus($status = '', $limit = 0)
    {
        $list = $this->getTicketIdsByStatus($status, $limit);

        $tickets = [];
        foreach($list as $id) {
            $tickets[] = $this->getTicketById((int) $id);
        }

        return $tickets;
    }
}
