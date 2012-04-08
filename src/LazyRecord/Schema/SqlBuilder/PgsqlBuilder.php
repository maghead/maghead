<?php
namespace LazyRecord\Schema\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\QueryBuilder;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlBuilder
    extends BaseBuilder
    implements BuilderInterface
{

    function buildColumnSql($schema, $column) {      
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->parent->driver->getQuoteColumn( $name );

        if( ! $column->autoIncrement )
            $sql .= ' ' . $type;

        if( $column->required || $column->notNull )
            $sql .= ' NOT NULL';
        elseif( $column->null )
            $sql .= ' NULL';


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
                $sql .= ' default ' . $this->parent->driver->inflate($default);
            }
        }

        if( $column->autoIncrement )
            $sql .= ' SERIAL'; // use pgsql built-in serial for auto increment column

        if( $column->primary )
            $sql .= ' PRIMARY KEY';


        if( $column->unique )
            $sql .= ' UNIQUE';

        // build reference
        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case SchemaDeclare::belongs_to:
            case SchemaDeclare::has_many:
            case SchemaDeclare::has_one:
                if( $name != 'id' && $rel['self']['column'] == $name ) 
                {
                    $fSchema = new $rel['foreign']['schema'];
                    $fColumn = $rel['foreign']['column'];
                    $fc = $fSchema->columns[$fColumn];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '.' . $fColumn;
                }
                break;
            }
        }
        return $sql;
    }

    public function createTable($schema)
    {
        $create = 'CREATE TABLE ' . $this->parent->driver->getQuoteTableName($schema->getTable()) . "( \n";
        $columnSql = array();
        foreach( $schema->columns as $name => $column ) {
            $columnSql[] = "\t" . $this->buildColumnSql( $schema, $column );
        }
        $create .= join(",\n",$columnSql);
        $create .= "\n);\n";
        return $create;
    }

    public function dropTable($schema)
    {
        return 'DROP TABLE IF EXISTS ' 
                . $this->parent->driver->getQuoteTableName( $schema->getTable() )
                . ' CASCADE';
    }

    public function build($schema)
    {
        $sqls = array();

        if( $this->parent->clean || $this->parent->rebuild ) {
            $sqls[] = $this->dropTable();
        }
        if( $this->parent->clean )
            return $sqls;

        $sqls[] = $this->createTable($schema);
        return $sqls;
    }

}
