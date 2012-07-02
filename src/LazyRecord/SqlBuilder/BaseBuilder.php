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

}




