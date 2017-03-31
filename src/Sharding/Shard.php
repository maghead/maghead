<?php

namespace Maghead\Sharding;

use Maghead\Sharding\Balancer\RandBalancer;
use Maghead\Manager\DataSourceManager;
use Maghead\Runtime\BaseRepo;
use SQLBuilder\Universal\Query\UUIDQuery;
use SQLBuilder\ArgumentArray;

class Shard
{
    /**
     * @var string 
     *
     * the id of the shard.
     */
    public $id;

    protected $readServers;

    protected $writeServers;

    /**
     * @var array
     *
     * the config of the shard.
     */
    protected $config;

    public function __construct($id, array $config, DataSourceManager $connectionManager, Balancer $balancer = null)
    {
        $this->id           = $id;
        $this->config       = $config;
        $this->readServers  = isset($config['read']) ? $config['read'] : [];
        $this->writeServers = isset($config['write']) ? $config['write'] : [];
        $this->connectionManager = $connectionManager;
        $this->balancer = $balancer ?: new RandBalancer;
    }

    /**
     * Add a read node for the list.
     *
     * @param string $nodeId
     * @param array $config
     */
    public function addReadNode($nodeId, array $config)
    {
        $this->readServers[$nodeId] = $config;
    }

    /**
     * Add a write node for the list.
     *
     * @param string $nodeId
     * @param array $config
     */
    public function addWriteNode($nodeId, array $config)
    {
        $this->writeServers[$nodeId] = $config;
    }

    /**
     * @return string the node Id for read.
     */
    public function selectReadNode()
    {
        return $this->balancer->select($this->readServers);
    }

    /**
     * @return string the node Id for write.
     */
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
