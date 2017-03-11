<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareColumn;
use Maghead\Schema\DeclareSchema;

class UUIDPrimaryKeyColumn extends DeclareColumn
{
    /**
     * BINARY(16) is the best column type for UUID.
     *
     * @see http://mysqlserverteam.com/storing-uuid-values-in-mysql-tables/
     */
    public function __construct(DeclareSchema $schema, $name = 'uuid', $type = 'BINARY', $length = 16)
    {
        parent::__construct($schema, $name);
        $this->type($type)
            ->isa('str')
            ->length($length)
            ->notNull()
            ->primary()
            ;

        $this->default(function($record, $args) {
            return \Ramsey\Uuid\Uuid::uuid4()->getBytes();
        });
        $this->deflate(function($val) {
            if ($val instanceof \Ramsey\Uuid\Uuid) {
                return $val->getBytes();
            }
            return $val;
        });
        $this->inflate(function($val) {
            return \Ramsey\Uuid\Uuid::fromBytes($val);
        });
    }
}
