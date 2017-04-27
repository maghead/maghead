<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\Manager\ShardManager;

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

    protected $instanceManager;

    protected $dataSourceManager;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->instanceManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
    }

    public function remove($mappingId, $nodeId)
    {
        // Connect to the instance that belongs to the node
        $conn = $this->dataSourceManager->connectInstance($nodeId);
        $nodeConfig = $this->dataSourceManager->getNodeConfig($nodeId);

        // TODO: migrate the chunks before removing the chunks

        // Drop the database for the new shard.
        $dbManager = new DatabaseManager($conn);
        $dbManager->drop($nodeConfig['database']);

        $this->config->removeDataSource($nodeId);
        $this->dataSourceManager->removeNode($nodeId);

        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $mapping = $shardManager->loadShardMapping($mappingId);
        $mapping->removeShardId($newNodeId);


        $shardManager->addShardMapping($mapping);
        $this->config->setShardingConfig($shardManager->getConfig());
    }
}
