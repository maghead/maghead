<?php
namespace LazyRecord\SqlBuilder;
use SQLBuilder\IndexBuilder;
use SQLBuilder\QueryBuilder;
use LazyRecord\Schema\SchemaDeclare;

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
        $sqls = array();
        foreach( $schema->columns as $name => $column ) {
            if ( $column->index ) {
                $indexName = is_string($column->index) ? $column->index 
                    : "idx_" . $schema->getTable() . "_" . $name;
                $builder = new IndexBuilder($this->driver);
                $builder->create( $indexName )
                    ->on( $schema->getTable() )
                    ->columns($name)
                    ;
                $sqls[] = $builder->build();
            }
        }
        return $sqls;
    }


    public function buildForeignKeys($schema)
    {
        return array(); // FIXME

        $sqls = array();
        if ($this->driver->type == 'sqlite') {
            return $sqls;
        }

        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case SchemaDeclare::belongs_to:
            case SchemaDeclare::has_many:
            case SchemaDeclare::has_one:
                if( isset($rel['self_column']) && $rel['self_column'] != 'id' ) 
                {
                    $n = $rel['self_column'];
                    $column = $schema->getColumn($n);
                    if ( $column->isa == "str" ) {
                        continue;
                    }

                    
                    $fSchema = new $rel['foreign_schema'];
                    $builder = new IndexBuilder($this->driver);
                    $sqls[] = $builder->addForeignKey(
                        $schema->getTable(),
                        $rel['self_column'],
                        $fSchema->getTable(),
                        $rel['foreign_column'],

                        // use cascade by default
                        // TODO: extract this as an option.
                        'CASCADE'
                    );
                }
            }
        }
        return $sqls;
    }


}




