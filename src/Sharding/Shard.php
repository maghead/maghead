<?php

namespace Maghead\Sharding;

use Maghead\Sharding\Balancer\RandBalancer;
use Maghead\Manager\ConnectionManager;
use Maghead\Runtime\BaseRepo;
use SQLBuilder\Universal\UUIDQuery;

class Shard
{
    /**
     * @var string 
     *
     * the id of the shard.
     */
    protected $id;

    protected $readServers;

    protected $writeServers;

    /**
     * @var array
     *
     * the config of the shard.
     */
    protected $config;

    public function __construct($id, array $config, ConnectionManager $connectionManager, Balancer $balancer = null)
    {
        $this->id           = $id;
        $this->config       = $config;
        $this->readServers  = $config['read'];
        $this->writeServers = $config['write'];
        $this->connectionManager = $connectionManager;
        $this->balancer = $balancer ?: new RandBalancer;
    }

    public function selectReadNode()
    {
        return $this->balancer->select($this->readServers);
    }

    public function selectWriteNode()
    {
        return $this->balancer->select($this->writeServers);
    }

    /**
     * @return \Maghead\Connection
     */
    public function selectReadConnection()
    {
        $nodeId = $this->balancer->select($this->readServers);
        return $this->connectionManager->getConnection($nodeId);
    }

    /**
     * @return \Maghead\Connection
     */
    public function selectWriteConnection()
    {
        $nodeId = $this->balancer->select($this->writeServers);
        return $this->connectionManager->getConnection($nodeId);
    }

    /**
     * Query UUID from the database.
     *
     * @return string
     */
    public function queryUUID()
    {
        // TODO: check if the database platform supports UUID generator
        $write  = $this->selectWriteConnection();
        $query  = new UUIDQuery;
        $driver = $write->getQueryDriver();
        $sql    = $query->toSql($driver, new ArgumentArray);
        return $write->query($sql)->fetchColumn(0);
    }


    /**
     * Alias method for createRepo.
     */
    public function repo(string $repoClass)
    {
        return $this->createRepo($repoClass);
    }


    /**
     * Create repo object from the selected nodes
     *
     * @return \Maghead\Runtime\BaseRepo
     */
    public function createRepo(string $repoClass)
    {
        return new $repoClass(
            $this->selectWriteConnection(),
            $this->selectReadConnection()
        );
    }
}
