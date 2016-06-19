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

function buildColumn($arg)
{
    if (is_string($arg)) {
        return new Column($arg);
    } else if (is_callable($arg)) {
        $column = new Column();
        if ($ret = call_user_func($arg, $column)) {
            return $ret;
        }
        return $column;
    } else if ($arg instanceof Column) {
        return $arg;
    } else {
        throw new InvalidArgumentException("Invalid column argument");
    }
}

class Migration implements Migratable
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
            $this->logger = $c['logger'] ?: Console::getInstance()->getLogger();
        }
        $this->logger = $logger;
        $this->builder = SqlBuilder::create($driver);
    }

    public static function getId()
    {
        $name = get_called_class() ?: get_class($this);
        if (preg_match('#_(\d+)$#', $name, $regs)) {
            return $regs[1];
        }
        // throw new Exception("Can't parse migration script ID from class name: " . $name);
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

    protected function alterTable($arg)
    {
        if ($arg instanceof DeclareSchema) {
            $table = $arg->getTable();
        } else {
            $table = $arg;
        }
        return new AlterTableQuery($arg);
    }

    /**
     * Rename column requires $schema object.
     */
    public function renameColumn($table, $oldColumn, $newColumn)
    {
        if ($this->driver instanceof MySQLDriver && is_string($newColumn)) {
            throw new InvalidArgumentException("MySQLDriver requires the new column to be a column definition object.");
        }
        $query = new AlterTableQuery($table);
        $query->renameColumn($oldColumn, $newColumn);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    public function dropColumn($table, $arg)
    {
        $column = buildColumn($arg);
        $query = new AlterTableQuery($table);
        $query->dropColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    public function modifyColumn($table, $arg)
    {
        $column = buildColumn($arg);
        $query = new AlterTableQuery($table);
        $query->modifyColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    public function addColumn($table, $arg)
    {
        $column = buildColumn($arg);
        $query = new AlterTableQuery($table);
        $query->addColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    /**
     * $this->createTable(function($s) {
     *      $s->column('title')->varchar(120);
     * });.
     */
    public function createTable($cb)
    {
        $ds = new DynamicSchemaDeclare();
        call_user_func($cb, $ds);
        $ds->build();

        $sqls = $this->builder->build($ds);
        $this->query($sqls);
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

    public function upgrade()
    {
        $this->logger->info('Nothing to do');
    }

    public function downgrade()
    {
        $this->logger->info('Nothing to do');
    }

    public function __call($m, $a)
    {
        if (method_exists($this->builder, $m)) {
            $this->logger->info($m);
            $sql = call_user_func_array(array($this->builder, $m), $a);
            $this->query($sql);
        } else {
            throw new BadMethodCallException("Method $m does not exist.");
        }
    }
}
