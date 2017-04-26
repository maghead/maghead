<?php

namespace Maghead\Command\ShardCommand\MappingCommand;

use Maghead\Command\BaseCommand;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;
use Maghead\Sharding\ShardMapping;
use Maghead\Manager\DataSourceManager;

class RemoveCommand extends BaseCommand
{
    public function brief()
    {
        return 'remove the shard mapping config';
    }

    public function arguments($args)
    {
        $args->add('mapping-id');
    }

    /*
        maghead shard mapping create [mappingId] --hash --shards "s1,s2,s3" --chunks 32
     */
    public function execute($mappingId)
    {
        $config = $this->getConfig(true);
        $configManager = new ConfigManager($config);
        $configManager->removeShardMappingById($mappingId);
        $configManager->save();
        return true;
    }
}
