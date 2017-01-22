<?php

namespace Maghead\SqlBuilder;

use Maghead\Schema;
use Maghead\Schema\SchemaInterface;
use Maghead\Schema\DeclareColumn;
use SQLBuilder\ArgumentArray;

/**
 * Schema SQL builder.
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlBuilder extends BaseBuilder
{
    public function buildColumnSql(SchemaInterface $schema, DeclareColumn $column)
    {
        $name = $column->name;
        $isa = $column->isa ?: 'str';
        if (!$column->type && $isa == 'str') {
            $column->type = 'text';
        }

        // Note that pgsql doesn't support unsigned integer primary key column
        if ($column->autoIncrement) {
            $column->unsigned = false;
        }

        $args = new ArgumentArray();
        $sql = $column->buildDefinitionSql($this->driver, $args);

        return $sql;
    }

    public function buildForeignKeys(SchemaInterface $schema)
    {
        return [];
    }

    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS '
                .$this->driver->quoteIdentifier($schema->getTable())
                .' CASCADE';
    }
}
