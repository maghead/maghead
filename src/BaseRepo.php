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

    /**
     * Find record.
     *
     * @param array condition array
     * @return BaseModel
     */
    // PHP 5.6 doesn't support static abstract
    // abstract static public function find($pkId);
    static protected function _stmFetch(PDOStatement $stm, array $args)
    {
        $stm->execute($args);
        $obj = $stm->fetch(PDO::FETCH_CLASS);
        $stm->closeCursor();
        return $obj;
    }
}
