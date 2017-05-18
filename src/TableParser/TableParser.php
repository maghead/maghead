<?php

namespace Maghead\TableParser;

use InvalidArgumentException;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Driver\SQLiteDriver;
use Maghead\Runtime\Connection;

class TableParser
{
    public static function create(Connection $c, BaseDriver $d)
    {
        if ($d instanceof MySQLDriver) {
            return new MysqlTableParser($c, $d);
        } else if ($d instanceof PgSQLDriver) {
            return new PgsqlTableParser($c, $d);
        } else if ($d instanceof SQLiteDriver) {
            return new SqliteTableParser($c, $d);
        }
        // This is not going to happen
        throw new InvalidArgumentException("table parser driver does not support {$d->getDriverName()} currently.");
    }
}
