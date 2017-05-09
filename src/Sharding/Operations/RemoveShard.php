<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;

use Maghead\Manager\ConfigManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\MetadataManager;
use Maghead\Manager\TableManager;
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
class RemoveShard extends BaseShardOperation
{
    public function remove($mappingId, $nodeId)
    {
        // Connect to the instance that belongs to the node
        $conn = $this->dataSourceManager->connectInstance($nodeId);
        $nodeConfig = $this->dataSourceManager->getNodeConfig($nodeId);

        // TODO: migrate the chunks before removing the chunks

        // Drop the database for the new shard.
        $dbManager = new DatabaseManager($conn);
        $dbManager->drop($nodeConfig['database']);

        $this->config->removeDatabase($nodeId);
        $this->dataSourceManager->removeNode($nodeId);

        $mapping = $this->shardManager->loadShardMapping($mappingId);
        $mapping->removeShardId($nodeId);

        $this->shardManager->setShardMapping($mapping);
    }
}
