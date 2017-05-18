<?php

namespace Maghead\Sharding\QueryMapper;

use Magsql\ToSqlInterface;
use Maghead\Sharding\ShardCollection;

interface QueryMapper
{
    /**
     * The map method map the select query
     *
     * @return array[shardId][]
     */
    public function map(ShardCollection $shards, $query);
}
