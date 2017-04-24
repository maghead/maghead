<?php

namespace Maghead\Runtime;

use PDO;
use PDOStatement;

class PDOFetcher
{
    /**
     * Find record.
     *
     * @param array condition array
     * @return BaseModel
     */
    // PHP 5.6 doesn't support static abstract
    public static function fetchOne(PDOStatement $stm, array $args, $style = PDO::FETCH_CLASS)
    {
        $stm->execute($args);
        $obj = $stm->fetch($style);

        // PDOStatement::closeCursor() frees up the connection to the server so
        // that other SQL statements may be issued, but leaves the statement in
        // a state that enables it to be executed again.
        $stm->closeCursor();
        return $obj;
    }

    /**
     * Fetch all record.
     *
     * @param array condition array
     * @return BaseModel
     */
    // PHP 5.6 doesn't support static abstract
    public static function fetchAll(PDOStatement $stm, array $args, $style = PDO::FETCH_CLASS)
    {
        $stm->execute($args);
        return $stm->fetchAll($style);
    }

}


