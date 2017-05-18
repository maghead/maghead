<?php

namespace Maghead\TableBuilder;

use InvalidArgumentException;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Driver\SQLiteDriver;

class TableBuilder
{
    public static function create(BaseDriver $driver, array $options = array())
    {
        if ($driver instanceof MySQLDriver) {
            return new MysqlBuilder($driver, $options);
        } elseif ($driver instanceof PgSQLDriver) {
            return new PgsqlBuilder($driver, $options);
        } elseif ($driver instanceof SQLiteDriver) {
            return new SqliteBuilder($driver, $options);
        }
        throw new InvalidArgumentException('Unsupported driver.');
    }
}
