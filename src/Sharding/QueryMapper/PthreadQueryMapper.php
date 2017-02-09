<?php

namespace Maghead\Sharding\QueryMapper;

use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\PDOMySQLDriver;
use Maghead\Manager\ConnectionManager;
use Worker;
use Pool;
use Threaded;
use PDO;

class PthreadQueryWorker extends Worker
{
    protected $dsn;

    protected $username;

    protected $password;

    protected $connectOptions;

    public static $link;

    public function __construct(string $dsn, $username = null, $password = null, array $connectOptions = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->connectOptions = $connectOptions;
    }

    public function connect()
    {
        if (count($this->connectOptions)) {
            return new PDO($this->dsn, $this->username, $this->password, $this->connectOptions);
        } else {
            return new PDO($this->dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
            ]);
        }
    }
}

class PthreadQueryJob extends Threaded {

    protected $sql;

    protected $args;

    protected $rows;

    public function __construct(string $sql, array $args)
    {
        $this->sql = $sql;
        $this->args = $args;
    }

    public function run()
    {
        $conn = $this->worker->connect();
        $stm = $conn->prepare($this->sql);
        $stm->execute(get_object_vars($this->args));
        $rows = $stm->fetchAll();
        $this->rows = serialize($rows);
        $this->g = true;
    }

    public function getRows()
    {
        return unserialize($this->rows);
    }

    public function isGarbage() : bool
    {
        return $this->g;
    }
}

class PthreadQueryMapper
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
            $conn = $this->connectionManager->getConnection($nodeId);
            $args = new ArgumentArray;
            $sql = $query->toSql($conn->getQueryDriver(), $args);
            $jobs[$nodeId] = $job = new PthreadQueryJob($sql, $args->toArray());
            $workers[$nodeId]->stack($job);
        }
        foreach ($nodeIds as $nodeId) {
            $workers[$nodeId]->join();
        }

        $results = [];
        foreach ($jobs as $nodeId => $job) {
            $results[$nodeId] = $rows = $job->getRows();
        }
        return $results;
    }
}
