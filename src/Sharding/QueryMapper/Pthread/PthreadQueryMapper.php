<?php

namespace Maghead\Sharding\QueryMapper\Pthread;

use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\PDOMySQLDriver;
use Maghead\Manager\ConnectionManager;
use Maghead\Sharding\QueryMapper\QueryMapper;

class PthreadQueryMapper implements QueryMapper
{
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }


    public function map(array $shards, string $repoClass, SelectQuery $query)
    {
        $nodeIds = [];
        foreach ($shards as $shard) {
            $nodeIds[] = $shard->getReadNode();
        }

        $workers = [];
        foreach ($nodeIds as $nodeId) {
            $ds = $this->connectionManager->getDataSource($nodeId);
            $workers[$nodeId] = $w = new PthreadQueryWorker($ds['dsn'], $ds['user'], $ds['pass'], $ds['connection_options']);
            $w->start();
        }

        $jobs = [];
        foreach ($nodeIds as $nodeId) {
            $worker = $workers[$nodeId];
            $conn = $this->connectionManager->getConnection($nodeId);
            $args = new ArgumentArray;
            $sql = $query->toSql($conn->getQueryDriver(), $args);
            $jobs[$nodeId] = $job = new PthreadQueryJob($sql, serialize($args->toArray()));
            $worker->stack($job);
        }

        foreach ($nodeIds as $nodeId) {
            $worker = $workers[$nodeId];
            $worker->join();
        }

        $results = [];
        foreach ($jobs as $nodeId => $job) {
            $results[$nodeId] = $job->getRows();
        }
        return $results;
    }
}
