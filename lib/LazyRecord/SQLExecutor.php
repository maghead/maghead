<?php

namespace LazyRecord;

class SQLExecutor extends \LazyRecord\DatabaseHandle {

    public $result; /* mysqli result */
    public $lastSQL;

    public function executeSQL( $sql , $drop_result = false ) {
        $this->lastSQL = $sql;
        $db = \LazyRecord\Engine::getInstance();
        return $db->connection()->query( $sql ); 
        // else return $this->result = $db->query( $sql );
    }

    public function getLastSQL() {
        return $this->lastSQL;
    }

    public function hasResult() {
        return $this->result != null;
    }

    public function getResult() {
        return $this->result;
    }

    public function freeResult() {
        if( $this->result ) {
            $this->result->close();
            $this->result = null;
        }
    }

}

?>
