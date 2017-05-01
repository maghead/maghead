<?php

namespace Maghead\Sharding;

use Maghead\Runtime\BaseRepo;

class ShardKeyStat
{
    public $shardKey;

    public $numberOfRows;

    protected $repo;

    public function __construct(BaseRepo $repo)
    {
        $this->repo = $repo;
        $this->shardKey = intval($this->shardKey);
        $this->numberOfRows = intval($this->numberOfRows);
    }
}
