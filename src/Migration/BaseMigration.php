<?php

namespace LazyRecord\Migration;

use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use LazyRecord\Console;

use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\ServiceContainer;
use CLIFramework\Logger;
use PDO;
use Exception;
use InvalidArgumentException;
use BadMethodCallException;

class BaseMigration
{
    /**
     * @var QueryDriver
     */
    protected $driver;

    /**
     * @var PDO object
     */
    protected $connection;

    /**
     * @var CLIFramework\Logger
     */
    protected $logger;

    /**
     * @var LazyRecord\SqlBuilder\BaseBuilder
     */
    protected $builder;

    public function __construct(PDO $connection, BaseDriver $driver, Logger $logger = null)
    {
        $this->connection = $connection;
        $this->driver     = $driver;
        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger = $c['logger'] ?: Console::getInstance()->getLogger();
        }
        $this->logger = $logger;
        $this->builder = SqlBuilder::create($driver);
    }

    /**
     * Deprecated, use query method instead.
     *
     * @deprecated
     */
    public function executeSql($sql)
    {
        return $this->query($sql);
    }

    /**
     * executeQuery method execute the query for objects that supports SQLBuilder\ToSqlInterface.
     *
     * @param ToSqlInterface $query
     */
    public function executeQuery(ToSqlInterface $query)
    {
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    protected function showSql($sql, $title = '')
    {
        if (strpos($sql, "\n") !== false) {
            $this->logger->info('Performing Query: '.$title);
            $this->logger->info($sql);
        } else {
            $this->logger->info('Performing Query: '.$sql);
        }
    }

    /**
     * Execute sql for migration.
     *
     * @param string $sql
     */
    public function query($sql, $title = '')
    {
        $sqls = (array) $sql;
        foreach ($sqls as $q) {
            $this->showSql($q, $title);

            return $this->connection->query($q);
        }
    }
}
