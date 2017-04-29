<?php

namespace Maghead\Command\ShardCommand\MappingCommand;

use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;
use Maghead\Sharding\ShardMapping;

use Maghead\Command\BaseCommand;
use Maghead\Manager\DataSourceManager;

class AddCommand extends BaseCommand
{
    public function brief()
    {
        return 'create the shard mapping config';
    }

    public function options($opts)
    {
        $opts->add('h|hash', 'hash based shard key')->defaultValue(true);

        $opts->add('k|key:', 'shard key');

        $opts->add('s|shard+', 'shard id')
            ->defaultValue(function() {
                $dataSourceManager = DataSourceManager::getInstance();
                return array_filter($dataSourceManager->getNodeIds(), function($nodeId) {
                    return $nodeId !== 'master';
                });
            });

        $opts->add('c|chunks:', 'the number of chunks')
            ->defaultValue(32);
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

        $dataSourceManager = DataSourceManager::getInstance();

        $mapping = new ShardMapping($mappingId, [
            'key'    => $this->options->key,
            'hash'   => $this->options->hash,
            'shards' => (array) $this->options->shard,
            'chunks' => [],
        ], $dataSourceManager);

        $chunkManager = new ChunkManager($mapping);
        $chunkIndexes = $chunkManager->distribute($this->options->chunks);

        var_dump($this->options->shard);
        // var_dump($chunkIndexes);

        $configManager = new ConfigManager($config);
        $configManager->addShardMapping($mapping);
        $configManager->save();
        return true;
    }
}
