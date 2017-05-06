<?php

namespace Maghead\Console\Command\ShardCommand\MappingCommand;

use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;
use Maghead\Sharding\ShardMapping;

use Maghead\Console\Command\BaseCommand;
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

        $opts->add('master', 'set new chunks to master');

        $opts->add('s|shard+', 'shard id')
            ->defaultValue(function () {
                $dataSourceManager = DataSourceManager::getInstance();
                return array_filter($dataSourceManager->getNodeIds(), function ($nodeId) {
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

        $mappingConfig = [
            'key'    => $this->options->key,
            'hash'   => $this->options->hash,
            'chunks' => [],
        ];
        if ($this->options->shard) {
            $mappingConfig['shards'] = (array) $this->options->shard;
        } elseif ($this->options->master) {
            $mappingConfig['shards'] = ['master'];
        }

        $mapping = new ShardMapping($mappingId, $mappingConfig, $dataSourceManager);

        $chunkManager = new ChunkManager($mapping);
        $chunkIndexes = $chunkManager->distribute($mappingConfig['shards'], $this->options->chunks);

        // var_dump($this->options->shard);
        $chunkGroups = [];
        foreach ($chunkIndexes as $index => $chunk) {
            $chunkGroups[ $chunk['shard'] ][] = $index;
        }
        foreach ($chunkGroups as $shardId => $chunkIndexes) {
            $this->logger->info("Shard {$shardId}:");
            foreach ($chunkIndexes as $chunkIndex) {
                $this->logger->info("- Chunk: $chunkIndex");
            }
        }

        $configManager = new ConfigManager($config);
        $configManager->addShardMapping($mapping);
        $configManager->save();
        return true;
    }
}
