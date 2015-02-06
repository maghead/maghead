<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\SchemaInterface;
use SQLBuilder\IndexBuilder;
use LazyRecord\Schema\RuntimeColumn;


/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlBuilder extends BaseBuilder
{

    public function buildColumnSql(SchemaInterface $schema, $column) {      
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->driver->quoteIdentifier( $name );

        if ( ! $column->autoIncrement ) {
            $sql .= ' ' . $type;
        }
        if ( $column->timezone ) {
            $sql .= ' with time zone';
        }

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


    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS ' 
                . $this->driver->quoteIdentifier( $schema->getTable() )
                . ' CASCADE';
    }

}
