<?php

namespace Maghead\Migration;

use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\BaseDriver;
use Maghead\Console;
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\DynamicSchemaDeclare;
use Maghead\SqlBuilder\SqlBuilder;
use Maghead\ServiceContainer;
use CLIFramework\Logger;
use PDO;
use InvalidArgumentException;

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
     * @var Maghead\SqlBuilder\BaseBuilder
     */
    protected $builder;

    public function __construct(PDO $connection, BaseDriver $driver, Logger $logger = null)
    {
        $this->connection = $connection;
        $this->driver = $driver;
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
            $this->connection->query($q);
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
        } elseif ($schema instanceof BaseModel && method_exists($schema, 'schema')) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } else {
            throw new InvalidArgumentException('Unsupported schema type');
        }
    }
}
