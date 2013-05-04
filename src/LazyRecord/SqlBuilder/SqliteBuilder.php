<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\QueryBuilder;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class SqliteBuilder
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
        $sql .= ' ' . $type;

        if( $column->required || $column->notNull )
            $sql .= ' NOT NULL';
        elseif( $column->null )
            $sql .= ' NULL';

        /**
         * if it's callable, we should not write the result into sql schema 
         */
        if( null !== ($default = $column->default) 
            && ! is_callable($column->default )  ) 
        {
            // for raw sql default value
            if( is_array($default) ) {
                $sql .= ' default ' . $default[0];
            } else {
                $sql .= ' default ' . $this->driver->inflate($default);
            }
        }

        if( $column->primary )
            $sql .= ' primary key';

        if( $column->autoIncrement )
            $sql .= ' autoincrement';

        if( $column->unique )
            $sql .= ' unique';

        /**
         * build sqlite reference
         *    create table track(
         *        trackartist INTEGER,
         *        FOREIGN KEY(trackartist) REFERENCES artist(artistid)
         *    )
         * @see http://www.sqlite.org/foreignkeys.html
         *
         * CREATE TABLE album(
         *     albumartist TEXT,
         *     albumname TEXT,
         *     albumcover BINARY,
         *     PRIMARY KEY(albumartist, albumname)
         *     );
         *
         * CREATE TABLE song(
         *     songid     INTEGER,
         *     songartist TEXT,
         *     songalbum TEXT,
         *     songname   TEXT,
         *     FOREIGN KEY(songartist, songalbum) REFERENCES album(albumartist, albumname)
         * );
         */
        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case SchemaDeclare::belongs_to:
            case SchemaDeclare::has_many:
            case SchemaDeclare::has_one:
                if( $name != 'id' && $rel['self_column'] == $name ) 
                {
                    $fSchema = new $rel['foreign_schema'];
                    $fColumn = $rel['foreign_column'];
                    $fc = $fSchema->columns[$fColumn];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '(' . $fColumn . ')';
                }
                break;
            }
        }
        return $sql;
    }

    public function dropTable($schema)
    {
        return 'DROP TABLE IF EXISTS ' 
            . $this->driver->getQuoteTableName( $schema->getTable() )
            . ';';
    }

    public function buildIndex($schema)
    {
        return array();
    }

    public function buildForeignKeys($schema) {
        return array();
    }

}
