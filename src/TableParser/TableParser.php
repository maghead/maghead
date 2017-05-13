<?php

namespace Maghead\TableParser;

use InvalidArgumentException;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;
use Maghead\Runtime\Connection;

class TableParser
{
    public static function create(Connection $conn, BaseDriver $d)
    {
        if ($d instanceof MySQLDriver) {
            return new MySQLTableParser($conn, $d);
        } else if ($d instanceof PgSQLDriver) {
            return new PgSQLTableParser($conn, $d);
        } else if ($d instanceof SQLiteDriver) {
            return new SQLiteTableParser($conn, $d);
        }
        // This is not going to happen
        throw new InvalidArgumentException("table parser driver does not support {$d->getDriverName()} currently.");
    }
}
