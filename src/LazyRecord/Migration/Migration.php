<?php
namespace LazyRecord\Migration;
use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Bind;

use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\ConnectionManager;
use LazyRecord\Console;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\ColumnDeclare;
use LazyRecord\SqlBuilder\SqlBuilder;

use PDO;
use PDOException;
use LogicException;


class Migration
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

    public function __construct($dsId)
    {
        $connectionManager = ConnectionManager::getInstance();
        $this->driver = $connectionManager->getQueryDriver($dsId);
        $this->connection = $connectionManager->getConnection($dsId);
        // $this->builder = new MigrationBuilder($this->driver);
        $this->logger  = Console::getInstance()->getLogger();

        $this->builder = SqlBuilder::create($this->driver);
    }

    public static function getId()
    {
        $name = get_called_class() ?: get_class($this);
        if( preg_match('#_(\d+)$#',$name,$regs) ) {
            return $regs[1];
        }
    }


    /**
     * Deprecated, use query method instead.
     */
    public function executeSql($sql)
    {
        return $this->query($sql);
    }


    /**
     * Execute sql for migration
     *
     * @param string $sql
     */
    public function query($sql, $title = NULL) 
    {
        $sql = (array) $sql;
        if ($title) {
            $this->logger->info('Executing query: '. $title);
        }
        foreach ($sql as $q) {
            $this->logger->info('Query: ' . $q);
            $stm = $this->connection->query($q);
            return $stm;
        }
    }

    public function dropColumn($table, $arg)
    {
        $query = new AlterTableQuery($table);
        if (is_callable($arg)) {
            $c = new Column;
            call_user_func($arg, $c);
            $query->dropColumn($c);
        } else if ($arg instanceof Column) {
            $query->dropColumn($arg);
        } else if (is_string($arg)) {
            $column = new Column($arg);
            $query->dropColumn($column);
        } else {
            if (isset($arg['name'])) {
                $column = new Column($arg['name']);
                $query->dropColumn($column);
            } else {
                throw new LogicException("Column name undefined.");
            }
        }
    }

    public function addColumn($table, $arg)
    {
        $query = new AlterTableQuery($table);
        if (is_callable($arg)) {
            $c = new Column;
            call_user_func($arg, $c);
            $query->addColumn($c);
        } else if ($arg instanceof Column) {
            $query->addColumn($arg);
        } else if (is_string($arg)) {
            $column = new Column($arg);
            $query->addColumn($column);
        } else {
            if (isset($arg['name'])) {
                $column = new Column($arg['name']);
                $query->addColumn($column);
            } else {
                throw new LogicException("Column name undefined.");
            }
        }
        $sql = $query->toSql($this->connection->createQueryDriver(), new ArgumentArray);
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

        if ($schema instanceof SchemaDeclare) {
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } elseif ($schema instanceof BaseModel && method_exists($schema,'schema')) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
            $sqls = $this->builder->build($schema);
            $this->query($sqls);
        } else {
            throw new Exception("Unsupported schema type");
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
        }
    }
}



