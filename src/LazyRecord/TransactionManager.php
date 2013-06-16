<?php
namespace LazyRecord;

class TransactionManager
{
    public $conn;

    /**
     * @var int active transaction counter.
     */
    public $transactionCounter = 0;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function begin() {
        if ($this->conn->beginTransaction() ) {
            $this->transactionCounter++;
        }
    }

    public function rollback() {
        if ( --$this->transactionCounter >= 0 ) {
            return $this->conn->rollback();
        }
    }

    public function commit() {
        if ( --$this->transactionCounter >= 0 ) {
            return $this->conn->commit();
        }
    }

    public function hasActive() {
        return $this->transactionCounter > 0;
    }
}



