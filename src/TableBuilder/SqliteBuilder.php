<?php

namespace Maghead\TableBuilder;

use Maghead\Schema\Schema;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Schema\Relationship\HasOne;
use Maghead\Schema\Relationship\HasMany;
use Maghead\Schema\Relationship\BelongsTo;
use Maghead\Schema\Relationship\ManyToMany;
use Maghead\Schema\DeclareColumn;
use Magsql\ArgumentArray;

/**
 * Schema SQL builder.
 *
 * @see http://www.sqlite.org/docs.html
 */
class SqliteBuilder extends BaseBuilder
{
    public function prepare()
    {
        return [
            // skip foreign key checks
            'PRAGMA foreign_keys = 0',
        ];
    }

    public function buildColumn(Schema $schema, DeclareColumn $column)
    {
        $name = $column->name;
        $isa = $column->isa ?: 'str';
        $type = $column->type;
        if (!$type && $isa == 'str') {
            $type = 'text';
        }

        // Note that sqlite doesn't support unsigned integer primary key column
        if ($column->autoIncrement) {
            $column->unsigned = false;
        }

        $args = new ArgumentArray();
        $sql = $column->buildDefinitionSql($this->driver, $args);

        /*
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
        foreach ($schema->relations as $rel) {
            if ($rel instanceof BelongsTo
             || $rel instanceof HasMany
             || $rel instanceof HasOne
            ) {
                // FIXME: remove "id"
                if ($name != 'id' && $rel['self_column'] == $name) {
                    $fSchema = new $rel['foreign_schema']();
                    $fColumn = $rel['foreign_column'];
                    $sql .= ' REFERENCES '.$fSchema->getTable().'('.$fColumn.')';
                }
            }
        }

        return $sql;
    }

    public function dropTable(Schema $schema)
    {
        return 'DROP TABLE IF EXISTS '
            .$this->driver->quoteIdentifier($schema->getTable())
            .';';
    }

    public function buildIndex(Schema $schema)
    {
        return [];
    }

    public function buildForeignKeys(Schema $schema)
    {
        return [];
    }
}
