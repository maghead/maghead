<?php

namespace Maghead\Migration;

use Magsql\Universal\Query\AlterTableQuery;
use Magsql\ToSqlInterface;
use Magsql\ArgumentArray;
use Magsql\Driver\BaseDriver;
use Maghead\Console\Application;
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\DynamicSchemaDeclare;
use Maghead\TableBuilder\TableBuilder;
use CLIFramework\Logger;
use PDO;
use Exception;
use RuntimeException;
use InvalidArgumentException;
use Maghead\Migration\Exception\MigrationException;
use Maghead\Runtime\Connection;

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
     * @var Maghead\TableBuilder\BaseBuilder
     */
    protected $builder;

    public function __construct(Connection $connection, BaseDriver $driver, Logger $logger)
    {
        $this->connection = $connection;
        $this->driver = $driver;
        $this->logger = $logger;
        $this->builder = TableBuilder::create($driver);
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
     * executeQuery method execute the query for objects that supports Magsql\ToSqlInterface.
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
            try {
                $this->connection->query($q);
            } catch (Exception $e) {
                throw new MigrationException("Migration failed", $this, $q,$e);
            }
        }
    }

    public function alterTable($arg)
    {
        if ($arg instanceof DeclareSchema) {
            $table = $arg->getTable();
        } else {
            $table = $arg;
        }

        return new AlterTableQuery($arg);
    }

    public function importSchema($schema)
    {
        $this->logger->info('Importing schema: '.get_class($schema));

        if ($schema instanceof DeclareSchema) {
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } elseif ($schema instanceof Record && method_exists($schema, 'schema')) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } else {
            throw new InvalidArgumentException('Unsupported schema type');
        }
    }
}
