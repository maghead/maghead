<?php

namespace LazyRecord;

class DatabaseHandle {

    /* etedb database object */
    static function handle( $name = null ) {
        static $handle;
        if( $handle )
            return $handle;

        $db = \LazyRecord\Engine::getInstance();
        $conn = $db->connection();
        return $handle = $conn->handle();
    }

}

?>
