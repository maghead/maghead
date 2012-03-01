<?php
namespace Lazy\SchemaSqlBuilder;
use Lazy\Schema\SchemaDeclare;
use Lazy\QueryDriver;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlDriver
    extends BaseDriver
    implements DriverInterface
{

    function buildColumnSql($schema, $column) {      
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->driver->getQuoteColumn( $name );

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
                $sql .= ' default ' . $this->driver->inflate($default);
            }
        }

        if( $column->autoIncrement )
            $sql .= ' serial';

        if( $column->primary )
            $sql .= ' primary key';


        if( $column->unique )
            $sql .= ' unique';

        // build reference
        // track(
        //		FOREIGN KEY(trackartist) REFERENCES artist(artistid)
        //		artist_id INTEGER REFERENCES artist
        // )
        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {

                // XXX: keep this
            case SchemaDeclare::belongs_to:
                $fs = new $rel['foreign']['schema'];
                $fcName = $rel['foreign']['column'];
                $fc = $fs->columns[$fcName];
                break;

            case SchemaDeclare::has_one:
                if( $rel['self']['column'] == $name ) { 
                    $fs = new $rel['foreign']['schema'];
                    $sql .= ' references ' . $fs->getTable();
                }
                break;
            }
        }
        return $sql;
    }

    public function build(SchemaDeclare $schema)
    {
        $sqls = array();

        $sqls[] = 'DROP TABLE IF EXISTS ' 
            . $this->driver->getQuoteTableName( $schema->getTable() )
            . ' CASCADE';


        $createSql = 'CREATE TABLE ' . $this->driver->getQuoteTableName($schema->getTable()) . "( \n";
        $columnSql = array();
        foreach( $schema->columns as $name => $column ) {
            $columnSql[] = "\t" . $this->buildColumnSql( $schema, $column );
        }
        $createSql .= join(",\n",$columnSql);
        $createSql .= "\n);\n";

        $sqls[] = $createSql;

        // generate sequence
        return $sqls;
    }

}
