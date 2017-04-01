<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Manager\ConnectionManager;
use Maghead\Config;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

/**
 * Given an instance ID:
 * 1. Connect to the instance
 * 2. Create a database
 * 3. Initialize the db schema
 */
class AllocateShard
{
    protected $config;

    protected $connectionManager;

    public function __construct(Config $config, ConnectionManager $connectionManager = null)
    {
        $this->config = $config;
        $this->connectionManager = $connectionManager ?: new ConnectionManager($config->getInstances());
    }

    public function allocateOn($instanceId, $newNodeId)
    {
        $conn = $this->connectionManager->connect($instanceId);

        // $dbManager = new DatabaseManager($this->connectionManager);

        // Get the dbname from master datasource
        /*
        $masterDs = $this->dataSourceManager->getMasterNodeConfig();
        $dsn = DSNParser::parse($masterDs['dsn']);
        $dbname = $dsn->getDatabaseName();
        */
    }
}
