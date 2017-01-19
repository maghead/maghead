<?php
namespace LazyRecord;
use LazyRecord\Connection;

class BaseRepo
{
    /**
     * @var Connection
     */
    protected $write;

    /**
     * @var Connection
     */
    protected $read;

    public function __construct(Connection $write, Connection $read = null)
    {
        $this->write = $write;
        $this->read = $read ? $read : $write;
    }

    public function getReadConnection()
    {
        return $this->read;
    }

    public function getWriteConnection()
    {
        return $this->write;
    }
}
