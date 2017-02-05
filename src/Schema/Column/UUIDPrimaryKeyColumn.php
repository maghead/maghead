<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareColumn;
use Maghead\Schema\DeclareSchema;

class UUIDPrimaryKeyColumn extends DeclareColumn
{
    /**
     * TODO: the best type for UUID in mysql is BINARY(36).
     */
    public function __construct(DeclareSchema $schema, $name = 'uuid', $type = 'binary', $length = 36)
    {
        parent::__construct($schema, $name);
        $this->type($type)
            ->isa('str')
            ->length($length)
            ->notNull()
            ->primary()
            ;
    }
}
