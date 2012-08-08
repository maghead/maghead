<?php
namespace LazyRecord;

class CacheManager
{
    public $backend;

    function using($backend) {
        $this->backend = $backend;
    }

    static function getInstance()
    {
        return new self;
    }

}

