<?php

namespace Maghead\Model;

use Maghead\Schema\DeclareSchema;

class MetadataSchema extends DeclareSchema
{
    public function schema()
    {
        $this->table('__meta__');

        $this->column('id')
            ->integer()
            ->primary()
            ->autoIncrement()
            ;

        $this->column('name')
            ->varchar(128)
            ->findable()
            ;

        $this->column('value')
            ->varchar(256)
            ->findable()
            ;

        $this->disableColumnAccessors();
    }
}
