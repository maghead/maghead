<?php

namespace Maghead;

use PDO;

class TransactionManager
{
    public $conn;

    /**
     * @var int active transaction counter.
     */
    public $transactionCounter = 0;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function begin()
    {
        if ($this->conn->beginTransaction()) {
            ++$this->transactionCounter;
        }
    }

    public function rollback()
    {
        if (--$this->transactionCounter >= 0) {
            return $this->conn->rollBack();
        }
    }

    public function inTransaction()
    {
        // before php 5.4, this returns integer.
        return (bool) $this->conn->inTransaction();
    }

    public function commit()
    {
        if (--$this->transactionCounter >= 0) {
            return $this->conn->commit();
        }
    }

    public function hasActive()
    {
        return $this->transactionCounter > 0;
    }

    public function setAutoCommit($on = true)
    {
        return $this->conn->query('SET autocommit='.($on ? '1' : '0').';');
    }

    public function lockTables($table, $type)
    {
        /* XXX: Currently only supports for mysql */
        return $this->conn->query("LOCK TABLES $table $type");
    }

    public function unlockTables()
    {
        /* XXX: Currently only supports for mysql */
        return $this->conn->query('UNLOCK TABLES;');
    }
}
