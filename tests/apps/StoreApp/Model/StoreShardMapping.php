<?php

namespace StoreApp\Model;

class StoreShardMapping
{
    static public function config()
    {
        return [
            'tables' => ['orders'], // This is something that we will define in the schema.
            'key' => 'store_id',
            'shards' => [ 's1', 's2' ],
            'chunks' => [
                'c1' => [ 'shard' => 's1' ],
                'c2' => [ 'shard' => 's2' ],
            ],
            'hash' => [
                'target1' => 'c1',
                'target2' => 'c2',
            ],
        ];
    }


}



