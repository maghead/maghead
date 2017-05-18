<?php

namespace Maghead\Sharding\QueryMapper\Pthread;

use Magsql\Universal\Query\CreateDatabaseQuery;
use Magsql\ArgumentArray;
use Magsql\Driver\PDOMySQLDriver;
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
        $this->start($shards);
        $jobs = $this->send($query);
        $this->wait();
        return $this->mergeJobsResults($jobs);
    }

    protected function start($shards)
    {
        foreach ($shards as $shardId => $shard) {
            $ds = $this->dataSourceManager->getNodeConfig($shardId);
            $this->workers[$shardId] = $w = new PthreadQueryWorker($ds['dsn'], $ds['user'], $ds['password'], $ds['connection_options']);
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

    protected function mergeJobsResults(array $jobs)
    {
        $results = [];
        foreach ($jobs as $nodeId => $job) {
            $results[$nodeId] = $job->getRows();
        }
        return $results;
    }
}
