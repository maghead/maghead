<?php

namespace Maghead\Sharding;

use Maghead\Sharding\Balancer\RandBalancer;
use Maghead\Manager\ConnectionManager;
use Maghead\Runtime\BaseRepo;

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

    public function selectReadNode()
    {
        return $this->balancer->select($this->config['read']);
    }

    public function selectWriteNode()
    {
        return $this->balancer->select($this->config['write']);
    }

    /**
     * @return \Maghead\Connection
     */
    public function selectReadConnection()
    {
        $nodeId = $this->balancer->select($this->config['read']);
        return $this->connectionManager->getConnection($nodeId);
    }

    /**
     * @return \Maghead\Connection
     */
    public function selectWriteConnection()
    {
        $nodeId = $this->balancer->select($this->config['write']);
        return $this->connectionManager->getConnection($nodeId);
    }

    /**
     * Create repo object from the selected nodes
     *
     * @return \Maghead\Runtime\BaseRepo
     */
    public function createRepo(string $repoClass)
    {
        $read = $this->selectReadConnection();
        $write = $this->selectWriteConnection();
        return new $repoClass($write, $read);
    }
}
