<?php
namespace LazyRecord;
use PDO;

class Connection extends PDO
{
    public function prepareAndExecute($sql, $args = array())
    {
        $stm = $this->prepare($sql);
        $stm->execute($args); // $success 
        return $stm;
    }

}



