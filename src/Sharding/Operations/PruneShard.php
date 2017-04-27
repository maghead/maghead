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
use Maghead\Runtime\BaseRepo;
use Maghead\Schema;
use Maghead\Schema\SchemaUtils;
use Maghead\TableBuilder\TableBuilder;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use CLIFramework\Logger;

class PruneShard
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

    public function pruneShard(ShardMapping $mapping, Shard $shard)
    {

    }

    public function prune($mappingId, array $schemas, $nodeId = null)
    {
        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $mapping = $shardManager->loadShardMapping($mappingId);

        $shardKey = $mapping->getKey();
        $shards = $mapping->loadShardCollection();
        $shardDispatcher = $shards->createDispatcher();

        // $schemas = SchemaUtils::findSchemasByConfig($this->config);
        $schemas = SchemaUtils::filterShardMappingSchemas($mappingId, $schemas);

        foreach ($shards as $shardId => $shard) {
            if ($nodeId && $shard->id !== $nodeId) {
                continue;
            }

            $conn = $shard->getWriteConnection();
            if (!$conn) {
                throw new InvalidArgumentException("Data source $shardId doesn't exist");
            }


            foreach ($schemas as $schema) {
                if ($schema->globalTable) {
                    continue;
                }

                $repo = $schema->newRepo($conn, $conn);
                $keys = $repo->fetchShardKeys();
                $migrationKeys = $shardDispatcher->filterMigrationKeys($shardId, $keys);

                if (!empty($migrationKeys)) {
                    $delete = $repo->delete();
                    $delete->where()->in($shardKey, $migrationKeys);
                    $delete->execute();
                }
            }
        }
    }
}
