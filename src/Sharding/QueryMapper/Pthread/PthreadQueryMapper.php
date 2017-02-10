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
        $nodeIds = $this->selectNodes($shards);
        $workers = $this->start($nodeIds);
        $jobs = $this->send($workers, $query);
        $this->wait($workers);
        return $this->mergeJobsResults($jobs);
    }

    protected function start(array $nodeIds)
    {
        $workers = [];
        foreach ($nodeIds as $nodeId) {
            $ds = $this->connectionManager->getDataSource($nodeId);
            $workers[$nodeId] = $w = new PthreadQueryWorker($ds['dsn'], $ds['user'], $ds['pass'], $ds['connection_options']);
            $w->start();
        }
        return $workers;
    }

    protected function send(array $workers, $query)
    {
        $jobs = [];
        foreach ($workers as $nodeId => $worker) {
            // For different connection, we have different query driver to build the sql statement.
            $conn = $this->connectionManager->getConnection($nodeId);
            $args = new ArgumentArray;
            $sql = $query->toSql($conn->getQueryDriver(), $args);
            $jobs[$nodeId] = $job = new PthreadQueryJob($sql, serialize($args->toArray()));
            $worker->stack($job);
        }
        return $jobs;
    }

    protected function wait(array $workers)
    {
        foreach ($workers as $worker) {
            $worker->join();
        }
    }

    protected function selectNodes(array $shards)
    {
        $nodeIds = [];
        foreach ($shards as $shard) {
            $nodeIds[] = $shard->selectReadNode();
        }
        return $nodeIds;
    }

    protected function mergeJobsResults(array $jobs)
    {
        $results = [];
        foreach ($jobs as $nodeId => $job) {
            $results[$nodeId] = $job->getRows();
        }
        return $results;
    }
}
