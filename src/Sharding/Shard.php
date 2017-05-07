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
     * the id of the shard/node.
     */
    public $id;

    /**
     * @var object DistributionStatus
     */
    protected $stats;

    protected $chunks;

    public function __construct($id, DataSourceManager $dataSourceManager)
    {
        $this->id           = $id;
        $this->dataSourceManager = $dataSourceManager;
    }

    public function setStats($stats)
    {
        $this->stats = $stats;
    }

    public function getStats()
    {
        return $this->stats;
    }

    public function getWriteConnection()
    {
        return $this->dataSourceManager->getWriteConnection($this->id);
    }

    public function getReadConnection()
    {
        return $this->dataSourceManager->getReadConnection($this->id);
    }

    /**
     * Query UUID from the database.
     *
     * TODO: remove this, use DB UUID instead.
     *
     * @return string
     */
    public function queryUUID()
    {
        // TODO: check if the database platform supports UUID generator
        $write  = $this->dataSourceManager->getWriteConnection($this->id);
        $query  = new UUIDQuery;
        $driver = $write->getQueryDriver();
        $sql    = $query->toSql($driver, new ArgumentArray);
        return $write->query($sql)->fetchColumn(0);
    }

    /**
     * Fetches the distinct shard key from the repo.
     */
    public function fetchShardKeys(BaseRepo $repo)
    {
        return $repo->fetchShardKeys();
    }

    /**
     * Alias method for createRepo.
     */
    public function repo(string $repoClass)
    {
        return $this->createRepo($repoClass);
    }


    /**
     * Create repo object from the selected nodes.
     *
     * @return \Maghead\Runtime\BaseRepo
     */
    public function createRepo(string $repoClass)
    {
        return new $repoClass(
            $this->dataSourceManager->getWriteConnection($this->id),
            $this->dataSourceManager->getReadConnection($this->id),
            $this
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function __debugInfo()
    {
        return [
            '_shardId_' => $this->id,
        ];
    }
}
