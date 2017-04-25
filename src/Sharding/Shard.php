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

    public function __construct($id, DataSourceManager $dataSourceManager)
    {
        $this->id           = $id;
        $this->dataSourceManager = $dataSourceManager;
    }

    /**
     * Query UUID from the database.
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
            $this->dataSourceManager->getWriteConnection($this->id),
            $this->dataSourceManager->getReadConnection($this->id)
        );
    }
}
