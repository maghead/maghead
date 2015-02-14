<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;


/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlBuilder extends BaseBuilder
{

    public function buildColumnSql(SchemaInterface $schema, $column) {      
        var_dump( get_class($column) ); 
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        if (!$column->type && $isa == 'str') {
            $column->type = 'text';
        }

        $sql = $this->driver->quoteIdentifier( $name );

        if ( ! $column->autoIncrement ) {
            $sql .= ' ' . $column->buildTypeSql($this->driver);
        }

        if ($column->timezone) {
            $sql .= ' with time zone';
        }

        if ($column->required || $column->null === true) {
            $sql .= ' NOT NULL';
        } elseif ( $column->null === true ) {
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
                $sql .= ' default ' . $this->driver->deflate($default);
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


    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS ' 
                . $this->driver->quoteIdentifier( $schema->getTable() )
                . ' CASCADE';
    }

}
