<?php
namespace LazyRecord\SqlBuilder;

class BaseBuilder
{
    public $rebuild;
    public $clean;
    public $driver;

    public function __construct($driver,$options = array())
    {
        $this->driver = $driver;
        if( isset($options['rebuild']) ) {
            $this->rebuild = $options['rebuild'];
        }
        if( isset($options['clean']) ) {
            $this->clean = $options['clean'];
        }
    }


    public function createTable($schema)
    {
        $sql = 'CREATE TABLE ' 
            . $this->driver->getQuoteTableName($schema->getTable()) . " ( \n";
        $columnSql = array();
        foreach( $schema->columns as $name => $column ) {
            if( $column->virtual )
                continue;
            $columnSql[] = '  ' . $this->buildColumnSql( $schema, $column );
        }
        $sql .= join(",\n",$columnSql);
        $sql .= "\n);\n";
        return $sql;
    }

    public function __get($name)
    {
        return $this->driver->$name;
    }

    public function build($schema) 
    {
        if( $schema instanceof \LazyRecord\BaseModel ) {
            $model = $schema;
            $schema = new \LazyRecord\Schema\DynamicSchemaDeclare($model);
        }
        elseif( ! $schema instanceof \LazyRecord\Schema\SchemaDeclare ) {
            throw new Exception("Unknown schema instance:" . get_class($schema) );
        }
        $sqls = $this->buildTable($schema);
        $indexSqls = $this->buildIndex($schema);
        return array_merge( $sqls , $indexSqls );
    }

    public function buildTable($schema)
    {
        $sqls = array();

        if( $this->clean || $this->rebuild ) {
            $sqls[] = $this->dropTable($schema);
        }
        if( $this->clean )
            return $sqls;

        $sqls[] = $this->createTable($schema);
        return $sqls;
    }

    public function buildIndex($schema) 
    {
        return array();
    }
}




