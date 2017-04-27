<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ConfigManager;

use Maghead\Connection;

use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\MetadataManager;
use Maghead\Manager\TableManager;
use Maghead\Config;
use Maghead\Schema;
use Maghead\Schema\SchemaUtils;
use Maghead\TableBuilder\TableBuilder;

use Maghead\DSN\DSN;

use CLIFramework\Logger;

use InvalidArgumentException;

/**
 * Given an instance ID:
 * 1. Connect to the instance
 * 2. Create a database
 * 3. Initialize the db schema
 * 4. Add node to the data source
 */
class AllocateShard extends BaseShardOperation
{

    /**
     * Build the database tables with the given schemas (will be filtered)
     */
    private function buildTables($mappingId, Connection $conn, array $schemas)
    {
        $schemas = SchemaUtils::filterShardMappingSchemas($mappingId, $schemas);

        $driver = $conn->getQueryDriver();

        $tableManager = new TableManager($conn, [
            'rebuild' => true,
            'clean' => false,
        ]);
        $tableManager->build($schemas);

        // Allocate MetadataManager to update migration timestamp
        $metadata = new MetadataManager($conn, $driver);
        $metadata['migration'] = time();
    }

    /**
     * Allocates a new shard
     */
    public function allocate($mappingId, $instanceId, $newNodeId)
    {
        // 1. Connects to the instance
        // 2. Create a new database for the new shard wit the nodeId
        $conn = $this->instanceManager->connectInstance($instanceId);
        if ($this->dataSourceManager->hasNode($newNodeId)) {
            throw new InvalidArgumentException("Node $newNodeId is already defined.");
        }

        $dbManager = new DatabaseManager($conn);
        $dbManager->create($newNodeId);

        // 3. Create a new node config from the instance node config.
        $nodeConfig = $this->instanceManager->getNodeConfig($instanceId);
        $nodeConfig['database'] = $newNodeId;
        $nodeConfig = DSN::update($nodeConfig);

        // 4. Register the node config into config and the connectionManager
        $this->config->addDataSource($newNodeId, $nodeConfig);
        $this->dataSourceManager->addNode($newNodeId, $nodeConfig);

        // 5. Create shard tables
        $schemas = SchemaUtils::findSchemasByConfig($this->config);
        $dbConnection = $this->dataSourceManager->connect($newNodeId);
        $this->buildTables($mappingId, $dbConnection, $schemas);

        $mapping = $this->shardManager->loadShardMapping($mappingId);
        $mapping->addShardId($newNodeId);
        $this->shardManager->addShardMapping($mapping);
        $this->config->setShardingConfig($this->shardManager->getConfig());
    }
}
