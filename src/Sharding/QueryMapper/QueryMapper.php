<?php

namespace Maghead\Sharding\QueryMapper;

use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;

interface QueryMapper
{
    /**
     * The map method map the select query
     *
     * @return array[shardId][]
     */
    public function map(array $shards, string $repoClass, SelectQuery $query);
}

