<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareSchema;

/**
 * @codeCoverageIgnore
 */
class UUIDTextPrimaryKeyColumn extends UUIDPrimaryKeyColumn
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
        parent::__construct($schema, $name, $type, $length);
    }
}
