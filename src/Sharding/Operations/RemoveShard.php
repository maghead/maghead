<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ConfigManager;
use Maghead\Manager\MetadataManager;
use Maghead\Manager\TableManager;
use Maghead\Config;
use Maghead\Schema;
use Maghead\Schema\SchemaUtils;
use Maghead\TableBuilder\TableBuilder;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use CLIFramework\Logger;

/**
 * 1. Drop the database
 * 2. Remove node from the data source
 */
class RemoveShard
{
    protected $config;

    protected $connectionManager;

    protected $dataSourceManager;

    protected $logger;

    public function __construct(Config $config, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connectionManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
    }

    public function remove($nodeId)
    {
        $conn = $this->dataSourceManager->connectInstance($nodeId);
        $queryDriver = $conn->getQueryDriver();

        $nodeConfig = $this->dataSourceManager->getNodeConfig($nodeId);
        $dsn = DSNParser::parse($nodeConfig['dsn']);
        $dbName = $dsn->getAttribute('dbname');

        // create new database for the new shard.
        $dbManager = new DatabaseManager($conn);
        $dbManager->drop($dbName);

        $this->config->removeDataSource($nodeId);
        $this->dataSourceManager->removeNode($nodeId);
    }
}
