<?php
namespace LazyRecord\Schema\Column;
use LazyRecord\Schema\ColumnDeclare;

class AutoIncrementPrimaryKeyColumn extends ColumnDeclare
{
    public function __construct()
    {
        parent::__construct('id');
        $this->isa('int')
            ->integer()
            ->notNull()
            ->primary()
            ->autoIncrement();
    }


    /**
     * Create primary key column with autoIncrement and unsigned.
     */
    static public function forMySQL($type = 'bigint')
    {
        $column = new ColumnDeclare('id');
        $column->isa('int');
        $column->type($type);
        $column->unsigned();
        $column->notNull();
        $column->primary()->autoIncrement();
        return $column;
    }

}



