<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\ShardMapping;

use Maghead\Manager\ConfigManager as BaseConfigManager;

class ConfigManager extends BaseConfigManager
{
    public function addShardMapping(ShardMapping $mapping)
    {
        $this->config['sharding']['mappings'][$mapping->id] = $mapping->toArray();
    }

    public function removeShardMappingById($mappingId)
    {
        unset($this->config['sharding']['mappings'][$mappingId]);
    }

    public function removeShardMapping(ShardMapping $mapping)
    {
        unset($this->config['sharding']['mappings'][$mapping->id]);
    }
}
