<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\QueryBuilder;
use SQLBuilder\IndexBuilder;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlBuilder
    extends BaseBuilder
    implements BuilderInterface
{

    public function buildColumnSql($schema, $column) {      
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->driver->getQuoteColumn( $name );

        if( ! $column->autoIncrement )
            $sql .= ' ' . $type;

        if ( $column->required || $column->notNull ) {
            $sql .= ' NOT NULL';
        } elseif ( $column->null ) {
            $sql .= ' NULL';
        }


        /* if it's callable, we should not write the result into sql schema */
        if( ($default = $column->default) !== null 
            && ! is_callable($column->default )  ) 
        {

            // raw sql default value
            if( is_array($default) ) {
                $sql .= ' default ' . $default[0];
            }
            else {
                /* XXX: 
                 * note that we sometime need the data source id from model schema define.
                 * $sourceId = $schema->getDataSourceId();
                 */

                /**
                 * Here we use query driver builder to inflate default value,
                 * But the value,
                 */
                $sql .= ' default ' . $this->driver->inflate($default);
            }
        }

        if( $column->autoIncrement )
            $sql .= ' SERIAL'; // use pgsql built-in serial for auto increment column

        if( $column->primary )
            $sql .= ' PRIMARY KEY';


        if( $column->unique )
            $sql .= ' UNIQUE';

        return $sql;
    }


    public function dropTable($schema)
    {
        return 'DROP TABLE IF EXISTS ' 
                . $this->driver->getQuoteTableName( $schema->getTable() )
                . ' CASCADE';
    }


    public function buildIndex($schema) 
    {
        $sqls = array();
        foreach( $schema->columns as $name => $column ) {
            if ( $column->index ) {
                $indexName = is_string($column->index) ? $column->index 
                    : "idx_" . $name;
                $builder = new IndexBuilder($this->driver);
                $builder->create( $indexName )
                    ->on( $schema->getTable() )
                    ->columns($name)
                    ;
                $sqls[] = $builder->build();
            }
        }

        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case SchemaDeclare::belongs_to:
            case SchemaDeclare::has_many:
            case SchemaDeclare::has_one:
                if( isset($rel['self_column']) && $rel['self_column'] != 'id' ) 
                {
                    $fSchema = new $rel['foreign_schema'];
                    $builder = new IndexBuilder($this->driver);
                    $sqls[] = $builder->addForeignKey(
                        $schema->getTable(),
                        $rel['self_column'],
                        $fSchema->getTable(),
                        $rel['foreign_column']
                    );
                }
                break;
            }
        }
        return $sqls;
    }
}
