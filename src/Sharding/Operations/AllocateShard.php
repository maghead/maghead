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
 * Given an instance ID:
 * 1. Connect to the instance
 * 2. Create a database
 * 3. Initialize the db schema
 * 4. Add node to the data source
 */
class AllocateShard
{
    protected $config;

    protected $connectionManager;

    protected $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connectionManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
    }

    public function allocate($instanceId, $newNodeId, $mappingId)
    {
        // TODO: Add a special instanceID for sqlite support
        $dbName = $newNodeId;
        $conn = $this->connectionManager->connectInstance($instanceId);
        $queryDriver = $conn->getQueryDriver();

        // create new database for the new shard.
        $dbManager = new DatabaseManager($conn);
        $dbManager->create($dbName);

        // create a new node config from the instance node config.
        $nodeConfig = $this->connectionManager->getNodeConfig($instanceId);

        // Update DSN with the new dbname (works for mysql and pgsql)
        $dsn = DSNParser::parse($nodeConfig['dsn']);
        $dsn->setAttribute('dbname', $dbName);
        $nodeConfig['database'] = $dbName;
        $nodeConfig['dsn'] = $dsn->__toString();
        $this->config->addDataSource($newNodeId, $nodeConfig);

        $this->connectionManager->addNode($newNodeId, $nodeConfig);

        // Setup shard schema
        $dbConn = $this->connectionManager->connect($newNodeId);

        $schemas = SchemaUtils::findSchemasByConfig($this->config, $this->logger);
        $schemas = SchemaUtils::filterShardMappingSchemas($mappingId, $schemas);

        $sqlBuilder = TableBuilder::create($queryDriver, [
            'rebuild' => true,
            'clean' => false,
        ]);
        $tableManager = new TableManager($dbConn, $sqlBuilder, $this->logger);
        $tableManager->build($schemas);

        // Allocate MetadataManager to update migration timestamp
        $metadata = new MetadataManager($dbConn, $queryDriver);
        $metadata['migration'] = time();

        // TODO: modify the shard mapping config
        // 1. add the shard server config in sharding.shards, default to read [ node ], write [ node ]
        // 2. add the shard server ID to the chunk list in the shard mapping.
        $this->config->stash['sharding']['mappings'][$mappingId]['chunks'][$newNodeId] = [ 'shard' => $newNodeId ];
        $this->config->stash['sharding']['mappings'][$mappingId]['shards'][] = $newNodeId;
    }
}
