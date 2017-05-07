<?php

namespace Maghead\Sharding;

use Maghead\Runtime\BaseRepo;

class ShardKeyStat
{
    public $shardKey;

    public $numberOfRows;

    public $hash;

    protected $repo;

    public function __construct(BaseRepo $repo)
    {
        $this->repo = $repo;
        $this->shardKey = intval($this->shardKey);
        $this->numberOfRows = intval($this->numberOfRows);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __debugInfo()
    {
        return [
            'shardKey'     => $this->shardKey,
            'numberOfRows' => $this->numberOfRows,
            'hash' => $this->hash,
        ];
    }
}
