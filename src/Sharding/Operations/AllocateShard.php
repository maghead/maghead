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
class AllocateShard
{
    protected $config;

    protected $instanceManager;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->instanceManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
    }

    /**
     * Build the database tables with the given schemas (will be filtered)
     */
    private function buildTables(Connection $conn, $mappingId, array $schemas)
    {
        $driver = $conn->getQueryDriver();

        $schemas = SchemaUtils::filterShardMappingSchemas($mappingId, $schemas);

        $sqlBuilder = TableBuilder::create($driver, [
            'rebuild' => true,
            'clean' => false,
        ]);

        $tableManager = new TableManager($conn, $sqlBuilder);
        $tableManager->build($schemas);

        // Allocate MetadataManager to update migration timestamp
        $metadata = new MetadataManager($conn, $driver);
        $metadata['migration'] = time();
    }

    /**
     * Allocates a new shard
     */
    public function allocate($instanceId, $newNodeId, $mappingId)
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
        $this->buildTables($dbConnection, $mappingId, $schemas);

        // TODO: modify the shard mapping config
        // 1. add the shard server config in sharding.shards, default to read [ node ], write [ node ]
        // 2. add the shard server ID to the chunk list in the shard mapping.
        $this->config->stash['sharding']['mappings'][$mappingId]['chunks'][$newNodeId] = [ 'shard' => $newNodeId ];
        $this->config->stash['sharding']['mappings'][$mappingId]['shards'][] = $newNodeId;
    }
}
