<?php
namespace LazyRecord\SqlBuilder;
use Exception;
use RuntimeException;
use LazyRecord\QueryDriver;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;

use LazyRecord\SqlBuilder\MysqlBuilder;
use LazyRecord\SqlBuilder\PgsqlBuilder;
use LazyRecord\SqlBuilder\SqliteBuilder;

class SqlBuilder
{
    static function create(BaseDriver $driver, array $options = array() ) 
    {
        if ($driver instanceof MySQLDriver) {

            return new MySQLBuilder($driver, $options);

        } else if ($driver instanceof PgSQLDriver) {

            return new PgSQLBuilder($driver, $options);

        } else if ($driver instanceof SQLiteDriver) {

            return new SQLiteBuilder($driver, $options);

        }

        $className = get_class($driver);
        preg_match('/PDO(\w+)Driver$/', $className, $regs);
        if (!$regs[1]) {
            throw new Exception("Can't create sqlbuilder driver class from: " . get_class($driver));
        }
        $class = 'LazyRecord\\SqlBuilder\\' . ucfirst(strtolower($regs[1])) . 'Builder';
        return new $class($driver, $options);
    }
}

