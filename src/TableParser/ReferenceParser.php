<?php

namespace Maghead\TableParser;

interface ReferenceParser
{
    /**
     * queryReferences should return a structure like this.
     *
     *    table column name => {
     *        'name'   => $row->CONSTRAINT_NAME,
     *        'table'  => $row->REFERENCED_TABLE_NAME,
     *        'column' => $row->REFERENCED_COLUMN_NAME,
     *    }
     */
    public function queryReferences($table);
}
