<?php

namespace Maghead\Sharding;

class Chunk
{
    public $index;

    public $from;

    public $config;

    protected $shard;

    public function __construct($index, $from, array $config)
    {
        $this->index  = $index;
        $this->from   = $from;
        $this->config = $config;
        $this->shard  = $config['shard'];
    }

    public function getShard()
    {
        return $this->shard;
    }
}
