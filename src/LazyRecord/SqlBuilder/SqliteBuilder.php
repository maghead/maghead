<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\QueryBuilder;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class SqliteBuilder extends BaseBuilder 
{
    public function buildColumnSql(SchemaInterface $schema, $column) {
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->driver->quoteIdentifier( $name );
        $sql .= ' ' . $type;

        if ($column->required || $column->null === false) {
            $sql .= ' NOT NULL';
        } elseif ($column->null === true) {
            $sql .= ' NULL';
        }

        /**
         * if it's callable, we should not write the result into sql schema 
         */
        if (null !== ($default = $column->default) 
            && ! is_callable($column->default )) 
        {
            // for raw sql default value
            if( is_array($default) ) {
                $sql .= ' default ' . $default[0];
            } else {
                $sql .= ' default ' . $this->driver->deflate($default);
            }
        }

        if ($column->primary)
            $sql .= ' primary key';

        if ($column->autoIncrement) {
            $sql .= ' autoincrement';
        }

        if ($column->unique) {
            $sql .= ' unique';
        }

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
            case Relationship::BELONGS_TO:
            case Relationship::HAS_MANY:
            case Relationship::HAS_ONE:
                if ($name != 'id' && $rel['self_column'] == $name)
                {
                    $fSchema = new $rel['foreign_schema'];
                    $fColumn = $rel['foreign_column'];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '(' . $fColumn . ')';
                }
                break;
            }
        }
        return $sql;
    }

    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS ' 
            . $this->driver->quoteIdentifier( $schema->getTable() )
            . ';';
    }

    public function buildIndex(SchemaInterface $schema)
    {
        return array();
    }

    public function buildForeignKeys(SchemaInterface $schema) {
        return array();
    }

}
