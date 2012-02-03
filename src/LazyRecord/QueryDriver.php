<?php
namespace LazyRecord;

class QueryDriver extends \SQLBuilder\Driver
{
    static function getInstance()
    {
        static $ins;
        return $ins ?: $ins = new self;
    }
}

