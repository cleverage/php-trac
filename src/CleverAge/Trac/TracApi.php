<?php

namespace CleverAge\Trac;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

use CleverAge\Trac\HttpClient\HttpClientInterface;

class TracApi
{
    const AUTH_NONE = 0;
    const AUTH_BASIC = 1;

    const STATUS_NEW        = 'new';
    const STATUS_ASSIGNED   = 'assigned';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_REOPENED   = 'reopened';
    const STATUS_CLOSED     = 'closed';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var CleverAge\Trac\HttpClient\HttpClientInterface
     */
    protected $client;

    // ------ INIT ------ \\

    public function __construct(array $config, HttpClientInterface $client)
    {
        $this->config = $this->resolveConfig($config);
        $this->client = $client;
        $this->client->setBaseUrl($this->config['apiurl']);
    }

    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(array(
                'url', 'ticket.class', 'auth', 'milestone.class'
            ))
            ->setOptional(array(
                'user.login', 'user.password'
            ))
            ->setDefaults(array(
                'auth' => self::AUTH_NONE,
                'ticket.class' => 'CleverAge\Trac\Model\Ticket',
                'milestone.class' => 'CleverAge\Trac\Model\Milestone'
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

        $config['apiurl'] = $config['url'] . ($config['auth'] == self::AUTH_NONE ? 'jsonrpc' : 'login/jsonrpc');

        return $config;
    }

    // ------ HTTP ------ \\

    protected function doRequest($method, array $params = array(), array $headers = array())
    {
        $queryParams = $this->prepareQueryParams($method, $params);
        return $this->client->doRequest($queryParams, array_merge($this->config['headers'], $headers));
    }

    protected function doManyRequests(array $requests, array $headers = array())
    {
        $finalRequests = array();

        foreach ($requests as $request) {
            $finalRequests[] = $this->prepareQueryParams($request[0], $request[1]);
        }

        return $this->client->doManyRequests($finalRequests, array_merge($this->config['headers'], $headers));
    }

    protected function prepareQueryParams($method, array $params = array())
    {
        return array(
            'params' => $params,
            'method' => $method,
        );
    }

    // ------ Public Functional Methods ------ \\

    public function getTicketIdsBy(array $filters = array(), $limit = 0)
    {
        $f = $this->parseFilters($filters);

        return $this->doRequest('ticket.query', array(($f ? $f.'&':'').'max='.$limit));
    }

    public function getTicketById($id)
    {
        $arrayFromApi = $this->doRequest('ticket.get', array($id));
        return $this->createTicketFromApi($arrayFromApi);
    }

    public function getManyTicketsByIds(array $ids)
    {
        if (empty($ids)) {
            return array();
        }

        $requests = array();
        foreach ($ids as $id) {
            $requests[] = array('ticket.get', array($id));
        }

        $arraysFromApi = $this->doManyRequests($requests);

        $tickets = array();

        foreach ($arraysFromApi as $arrayFromApi) {
            $tickets[] = $this->createTicketFromApi($arrayFromApi);
        }

        return $tickets;
    }

    /**
     * @param boolean|null $completed
     * @return array<Model\Milestone>
     */
    public function getMilestones($completed = null)
    {
        $ids = $this->doRequest('ticket.milestone.getAll');

        if (empty($ids)) {
            return array();
        }

        $requests = array();
        foreach ($ids as $id) {
            $requests[] = array('ticket.milestone.get', array($id));
        }

        $arraysFromApi = $this->doManyRequests($requests);

        $milestones = array();

        foreach ($arraysFromApi as $arrayFromApi) {
            if (
                (true === $completed && $arrayFromApi['completed'] != '0')
            || (false === $completed && $arrayFromApi['completed'] == '0')
            || null === $completed
            ) {
                $milestones[] = $this->createMilestoneFromApi($arrayFromApi);
            }
        }

        return $milestones;
    }

    public function updateTicket($id, array $attributes = array(), $comment = '', $notify = false, $author = '', \DateTime $updateAt = null)
    {
        if (is_null($updateAt)) {
            $updateAt = new \DateTime();
        }

        $request = array(
            $id,
            $comment,
            $attributes,
            $notify ? '1' : '0',
            $author,
            $updateAt->format('Y-m-d H:i:s'),
        );

        $arrayFromApi = $this->doRequest('ticket.update', $request);

        return $this->createTicketFromApi($arrayFromApi);
    }

    // ------ Aggregations ------ \\

    public function getTicketListBy($filters = array(), $limit = 0)
    {
        $ids = $this->getTicketIdsBy($filters, $limit);

        return $this->getManyTicketsByIds($ids);
    }

    // ------ Tools ------ \\
    /**
     * @param array $arrayFromApi
     * @return Model\Ticket
     */
    protected function createTicketFromApi(array $arrayFromApi)
    {
        $class = $this->config['ticket.class'];
        return new $class($this->config['url'], $arrayFromApi);
    }

    /**
     * @param array $arrayFromApi
     * @return \CleverAge\Trac\Model\Milestone
     */
    protected function createMilestoneFromApi(array $arrayFromApi)
    {
        $class = $this->config['milestone.class'];
        return new $class($arrayFromApi);
    }

    protected function parseFilters(array $filters)
    {
        $f = array();

        foreach ($filters as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vk) {
                    $f[] = $k.'='.$vk;
                }
            } else {
                $f[] = $k.'='.$v;
            }
        }

        return implode('&', $f);
    }
}
