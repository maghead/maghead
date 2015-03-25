<?php
namespace LazyRecord\Migration;
use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Bind;

use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\ConnectionManager;
use LazyRecord\Console;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\ColumnDeclare;
use LazyRecord\SqlBuilder\SqlBuilder;

use PDOException;
use LogicException;


class Migration
{
    public $driver;
    public $connection;
    public $logger;

    public function __construct($dsId)
    {
        $connectionManager = ConnectionManager::getInstance();
        $this->driver = $connectionManager->getQueryDriver($dsId);
        $this->connection = $connectionManager->getConnection($dsId);
        // $this->builder = new MigrationBuilder($this->driver);
        $this->logger  = Console::getInstance()->getLogger();
    }

    public static function getId()
    {
        $name = get_called_class() ?: get_class($this);
        if( preg_match('#_(\d+)$#',$name,$regs) ) {
            return $regs[1];
        }
    }

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
            $this->logger->info('Executing task: '. $title);
        }
        foreach( (array) $sql as $q ) {
            $this->logger->info('Query: ' . $q);
            $stm = $this->connection->query($q);
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
                $query->addColumn($column);
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
        $this->executeSql($sql);
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

        $builder = SqlBuilder::create($this->driver);
        $sqls = $builder->build($ds);
        $this->executeSql($sqls);
    }

    public function importSchema($schema) {
        $builder = SqlBuilder::create($this->driver);
        if( is_a($schema,'LazyRecord\\Schema\\SchemaDeclare',true) ) {
            $sqls = $builder->build($schema);
            $this->executeSql($sqls);
        } 
        elseif( is_a($schema,'LazyRecord\\BaseModel',true) && method_exists($schema,'schema') ) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
            $sqls = $builder->build($schema);
            $this->executeSql($sqls);
        }
    }

    /**
     * Execute migration sql builder commands
     *
     * @param string $m method name
     * @param array $a method arguments
     */
    public function executeCommand($m,$a) 
    {
        $this->logger->info($m);
        $builder = SqlBuilder::create($this->driver);
        $sql = call_user_func_array(array($builder,$m) , $a );
        $this->executeSql($sql);
    }

    public function upgrade() {
    }

    public function downgrade() { 
    }

    public function __call($m,$a) {
        $this->executeCommand($m,$a);
    }
}



