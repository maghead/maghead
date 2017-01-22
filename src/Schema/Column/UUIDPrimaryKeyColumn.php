<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareColumn;

class UUIDPrimaryKeyColumn extends DeclareColumn
{
    /**
     * TODO: the best type for UUID in mysql is BINARY(36).
     */
    public function __construct($name = 'uuid', $type = 'char', $length = 36)
    {
        parent::__construct($name);
        $this->type($type)
            ->isa('str')
            ->length($length)
            ->notNull()
            ->primary()
            ;
    }
}
