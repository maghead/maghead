<?php

namespace Maghead\TableBuilder;

use Exception;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;

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
        throw new Exception('Unsupported driver');
    }
}
