<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareColumn;
use Maghead\Schema\DeclareSchema;

class UUIDTextColumn extends DeclareColumn
{
    /**
     * BINARY(16) is the best column type for UUID.
     *
     * @see http://mysqlserverteam.com/storing-uuid-values-in-mysql-tables/
     *
     * @param number $length The length for the uuid like '00000006-b6d0-4f25-946a-87b9917b6f40'
     */
    public function __construct(DeclareSchema $schema, $name = 'uuid', $type = 'CHAR', $length = 36)
    {
        parent::__construct($schema, $name);
        $this->type($type)
            ->isa('str')
            ->length($length)
            ->primary()
            ->notNull()
            ;
    }
}
