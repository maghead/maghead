<?php

namespace Maghead\Sharding;

class ShardKeyStat
{
    public $shardKey;

    public $numberOfRows;

    public function __construct()
    {
        $this->shardKey = intval($this->shardKey);
        $this->numberOfRows = intval($this->numberOfRows);
    }
}
