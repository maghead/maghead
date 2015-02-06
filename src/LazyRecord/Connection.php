<?php
namespace LazyRecord;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PDODriverFactory;
use PDO;

class Connection extends PDO
{
    public function prepareAndExecute($sql, $args = array())
    {
        $stm = $this->prepare($sql);
        $stm->execute($args); // $success 
        return $stm;
    }

    public function createQueryDriver() {
        return PDODriverFactory::create($this);
    }
}



