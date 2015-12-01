<?php
namespace LazyRecord\Migration;
use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Bind;
use SQLBuilder\Driver\BaseDriver;
use LazyRecord\ConnectionManager;
use LazyRecord\Console;
use LazyRecord\Migration\Migratable;
use LazyRecord\Schema\DeclareColumn;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\ServiceContainer;
use PDO;
use PDOException;
use Exception;
use LogicException;
use InvalidArgumentException;
use BadMethodCallException;

class Migration implements Migratable
{
    /**
     * @var QueryDriver
     */
    public $driver;


    /**
     * @var PDO object
     */
    public $connection;

    /**
     * @var CLIFramework\Logger
     */
    public $logger;

    /**
     * @var LazyRecord\SqlBuilder\BaseBuilder
     */
    public $builder;

    public function __construct(BaseDriver $driver, PDO $connection)
    {
        $c = ServiceContainer::getInstance();
        $this->driver = $driver;
        $this->connection = $connection;
        $this->logger  = $c['logger'] ?: Console::getInstance()->getLogger();
        $this->builder = SqlBuilder::create($driver);
    }

    public static function getId()
    {
        $name = get_called_class() ?: get_class($this);
        if (preg_match('#_(\d+)$#',$name,$regs)) {
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


    public function executeQuery(ToSqlInterface $query)
    {
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }


    public function showSql($sql, $title = '')
    {
        if (strpos($sql,"\n") !== false) {
            $this->logger->info('Performing Query: ' . $title);
            $this->logger->info($sql);
        } else {
            $this->logger->info('Performing Query: ' . $sql);
        }
    }

    /**
     * Execute sql for migration
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

    public function alterTable($table)
    {
        $query = new AlterTableQuery($table);
        return $query;
    }

    public function dropColumnByClosure($table, callable $closure)
    {
        $query = new AlterTableQuery($table);
        $column = new Column;
        if ($ret = call_user_func($arg, $column)) {
            $column = $ret;
        }
        $query->dropColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    public function dropColumnByName($table, $columnName)
    {
        $column = new Column($arg);

        $query = new AlterTableQuery($table);
        $query->dropColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    public function dropColumn($table, Column $column)
    {
        $query = new AlterTableQuery($table);
        $query->dropColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    public function modifyColumnByCallable($table, callable $cb)
    {
        $query = new AlterTableQuery($table);
        $column = new Column;
        if ($ret = call_user_func($arg, $column)) {
            $column = $ret;
        }
        $query->modifyColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    public function modifyColumn($table, Column $column)
    {
        $query = new AlterTableQuery($table);
        $query->modifyColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    public function addColumnByCallable($table, callable $cb)
    {
        $query = new AlterTableQuery($table);
        $column = new Column;
        if ($ret = call_user_func($cb, $column)) {
            $column = $ret;
        }
        $query->addColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    public function addColumn($table, Column $column)
    {
        $query = new AlterTableQuery($table);
        $query->addColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray);
        $this->query($sql);
    }

    /**
     * $this->createTable(function($s) {
     *      $s->column('title')->varchar(120);
     * });
     */
    public function createTable($cb) 
    {
        $ds =  new DynamicSchemaDeclare;
        call_user_func($cb,$ds);
        $ds->build();

        $sqls = $this->builder->build($ds);
        $this->query($sqls);
    }

    public function importSchema($schema)
    {
        $this->logger->info("Importing schema: " . get_class($schema));

        if ($schema instanceof DeclareSchema) {
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } elseif ($schema instanceof BaseModel && method_exists($schema,'schema')) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } else {
            throw new InvalidArgumentException("Unsupported schema type");
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

    public function __call($m,$a) {
        if (method_exists($this->builder, $m)) {
            $this->logger->info($m);
            $sql = call_user_func_array(array($this->builder,$m) , $a );
            $this->query($sql);
        } else {
            throw new BadMethodCallException("Method $m does not exist.");
        }
    }
}



