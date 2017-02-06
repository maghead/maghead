<?php

namespace Maghead\Sharding;

use Maghead\Sharding\Balancer\RandBalancer;
use Maghead\Manager\ConnectionManager;

class Shard
{
    /**
     * @var string 
     *
     * the id of the shard.
     */
    protected $id;

    /**
     * @var array
     *
     * the config of the shard.
     */
    protected $config;

    public function __construct($id, array $config, ConnectionManager $connectionManager, Balancer $balancer = null)
    {
        $this->id = $id;
        $this->config = $config;
        $this->connectionManager = $connectionManager;
        $this->balancer = $balancer ?: new RandBalancer;
    }

    public function getReadConnection()
    {
        $nodeId = $this->balancer->select($this->config['read']);
        return $this->connectionManager->getConnection($nodeId);
    }

    public function getWriteConnection()
    {
        $nodeId = $this->balancer->select($this->config['write']);
        return $this->connectionManager->getConnection($nodeId);
    }
}
