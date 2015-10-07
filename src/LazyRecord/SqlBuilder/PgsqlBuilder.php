<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\DeclareColumn;
use SQLBuilder\ArgumentArray;


/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class PgsqlBuilder extends BaseBuilder
{

    public function buildColumnSql(SchemaInterface $schema, DeclareColumn $column) {
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        if (!$column->type && $isa == 'str') {
            $column->type = 'text';
        }
        $args = new ArgumentArray;
        $sql = $column->buildDefinitionSql($this->driver, $args);
        return $sql;
    }


    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS ' 
                . $this->driver->quoteIdentifier( $schema->getTable() )
                . ' CASCADE';
    }

}
