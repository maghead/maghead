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

class PruneShard
{
    protected $config;

    protected $connectionManager;

    protected $dataSourceManager;

    protected $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connectionManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
    }

    public function prune($nodeId, $mappingId)
    {
        $conn = $this->dataSourceManager->connect($nodeId);
        if (!$conn) {
            throw new InvalidArgumentException("Data source $nodeId doesn't exist");
        }

        $queryDriver = $conn->getQueryDriver();

        $schemas = SchemaUtils::findSchemasByConfig($this->config, $this->logger);
        $schemas = SchemaUtils::filterShardMappingSchemas($mappingId, $schemas);

        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $shardMapping = $shardManager->getShardMapping($mappingId);
        $shardKey = $shardMapping->getKey();

        $shardDispatcher = $shardManager->createShardDispatcherOf($mappingId);

        foreach ($schemas as $schema) {
            if ($schema->globalTable) {
                continue;
            }

            $repo = $schema->newRepo($conn, $conn);

            $q = $repo->select("DISTINCT {$shardKey}");
            $keys = $q->fetchColumn(0);

            $excludedShardKeys = array_filter($keys, function($key) use ($shardDispatcher, $nodeId) {
                return $shardDispatcher->dispatchShard($key) != $nodeId;
            });

            if (!empty($excludedShardKeys)) {
                $q = $repo->delete();
                $q->where()->in($shardKey, $excludedShardKeys);
                $q->execute();
            }
        }
    }
}
