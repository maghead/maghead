<?php

namespace Maghead\Sharding\QueryMapper\Pthread;

use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\PDOMySQLDriver;
use Maghead\Manager\DataSourceManager;
use Maghead\Sharding\QueryMapper\QueryMapper;
use Maghead\Sharding\ShardCollection;

class PthreadQueryMapper implements QueryMapper
{
    protected $dataSourceManager;

    protected $workers = [];

    public function __construct(DataSourceManager $dataSourceManager)
    {
        $this->dataSourceManager = $dataSourceManager;
    }

    public function map(ShardCollection $shards, $query)
    {
        $nodeIds = $this->selectReadNodes($shards);
        $this->start($nodeIds);
        $jobs = $this->send($query);
        $this->wait();
        return $this->mergeJobsResults($jobs);
    }

    protected function start(array $nodeIds)
    {
        foreach ($nodeIds as $nodeId) {
            $ds = $this->dataSourceManager->getNodeConfig($nodeId);
            $this->workers[$nodeId] = $w = new PthreadQueryWorker($ds['dsn'], $ds['user'], $ds['pass'], $ds['connection_options']);
            $w->start();
        }
    }

    protected function send($query)
    {
        $jobs = [];
        foreach ($this->workers as $nodeId => $worker) {
            // For different connection, we have different query driver to build the sql statement.
            $conn = $this->dataSourceManager->getConnection($nodeId);
            $args = new ArgumentArray;
            $sql = $query->toSql($conn->getQueryDriver(), $args);
            $jobs[$nodeId] = $job = new PthreadQueryJob($sql, serialize($args->toArray()));
            $worker->stack($job);
        }
        return $jobs;
    }

    protected function wait()
    {
        foreach ($this->workers as $worker) {
            $worker->join();
        }
    }

    protected function selectReadNodes(ShardCollection $shards)
    {
        $nodeIds = [];
        foreach ($shards as $shardId => $shard) {
            $nodeIds[$shardId] = $shard->selectReadNode();
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
