<?php

namespace Maghead\Sharding;

use ArrayObject;

class ShardingConfig extends ArrayObject
{
    public function setShardMapping($mappingId, array $config)
    {
        $this['mappings'][$mappingId] = $config;
    }

    public function getShardMapping($mappingId)
    {
        return $this['mappings'][$mappingId];
    }

    public function removeShardMapping($mappingId)
    {
        unset($this['mappings'][$mappingId]);
    }
}
