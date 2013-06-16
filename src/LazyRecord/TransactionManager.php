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
        $this->conn->rollback();
        $this->transactionCounter--;
    }

    public function commit() {
        $this->conn->commit();
        $this->transactionCounter--;
    }

    public function hasActive() {
        return $this->transactionCounter > 0;
    }
}



