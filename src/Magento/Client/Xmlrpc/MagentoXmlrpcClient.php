<?php

namespace Magento\Client\Xmlrpc;

use GuzzleHttp\Client;
use GuzzleHttp\Collection;
use Ivory\HttpAdapter\ConfigurationInterface;

class MagentoXmlrpcClient extends Client
{
    /**
     * @var boolean
     */
    protected $autoCloseSession = false;

    /**
     * @var \fXmlRpc\Client
     */
    private $client;

    protected $configCollection;

    /** @var  \Ivory\HttpAdapter\ConfigurationInterface */
    protected $adapterConfiguration;

    function __construct(Collection $configCollection)
    {
        $this->configCollection = $configCollection;
        parent::__construct($configCollection->toArray());
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Client\Xmlrpc\MagentoXmlrpcClient
     */
    public static function factory($config = array())
    {
        $defaults = array(
            'session' => '',
        );

        $required = array(
            'base_url',
            'api_user',
            'api_key',
            'session',
        );

        // Instantiate the Acquia Search plugin.
        $config = Collection::fromConfig($config, $defaults, $required);
        return new static($config);
    }



    /**
     * @param bool $autoClose
     *
     * @return \Magento\Client\Xmlrpc\MagentoXmlrpcClient
     */
    public function autoCloseSession($autoClose = true)
    {
        $this->autoCloseSession = $autoClose;
        return $this;
    }

    /**
     * Ends the session if applicable.
     */
    public function __destruct()
    {
        if ($this->autoCloseSession && $this->client) {
            $this->client->call('endSession', array($this->getConfig('session')));
        }
    }

    /**
     * @return \fXmlRpc\Client
     */
    public function getClient()
    {
        if (!isset($this->client)) {
            $uri = rtrim($this->configCollection->get('base_url'), '/') . '/api/xmlrpc/';

            /** Guzzle 4+ (http://guzzlephp.org/) */
            $this->client = new \fXmlRpc\Client(
                $uri,
                new \fXmlRpc\Transport\HttpAdapterTransport(new \Ivory\HttpAdapter\GuzzleHttpHttpAdapter($this, $this->adapterConfiguration))
            );
        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getSession()
    {
        $session = $this->configCollection->get('session');
        if (!$session) {
            $this->autoCloseSession = true;
            $session = $this->getClient()->call('login', array(
                $this->configCollection->get('api_user'),
                $this->configCollection->get('api_key')
            ));
            $this->configCollection->set('session', $session);
        }

        return $session;
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return array
     *
     * @throws \fXmlRpc\Exception\ResponseException
     */
    public function call($method, array $params = array())
    {
        $params = array($this->getSession(), $method, $params);
        return $this->getClient()->call('call', $params);
    }

    /**
     * @return mixed
     */
    public function getAdapterConfiguration()
    {
        return $this->adapterConfiguration;
    }

    /**
     * @param ConfigurationInterface $adapterConfiguration
     */
    public function setAdapterConfiguration(ConfigurationInterface $adapterConfiguration)
    {
        $this->adapterConfiguration = $adapterConfiguration;
    }
}
