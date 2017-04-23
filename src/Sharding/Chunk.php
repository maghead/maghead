<?php

namespace Maghead\Sharding;

class Chunk
{
    public $index;

    public $config;

    protected $shard;

    public function __construct($index, array $config)
    {
        $this->index = $index;
        $this->config = $config;
        $this->shard = $config['shard'];
    }

    public function getShard()
    {
        return $this->shard;
    }
}
