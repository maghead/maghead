<?php

namespace Maghead\Sharding\QueryMapper\Pthread;

use Threaded;

class PthreadQueryJob extends Threaded {

    protected $sql;

    protected $args;

    protected $result;

    public function __construct(string $sql, string $args)
    {
        $this->sql = $sql;
        $this->args = $args;
    }

    public function run()
    {
        $conn = $this->worker->connect();
        $stm = $conn->prepare($this->sql);
        $args = unserialize($this->args);
        $stm->execute($args);
        $rows = $stm->fetchAll();
        $this->result = serialize($rows);
    }

    public function getRows()
    {
        return unserialize($this->result);
    }

    public function isGarbage() : bool
    {
        return $this->result ? true : false;
    }
}
