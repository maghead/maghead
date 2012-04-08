<?php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinSchemaDeclare;
use LazyRecord\Schema;

class MetadataSchema extends MixinSchemaDeclare
{
    function schema()
    {
        $this->column('updated_on')
            ->isa('DateTime')
            ->defaultBuilder( function() { return date('c'); } )
            ->datetime();

        $this->column('created_on')
            ->isa('DateTime')
            ->defaultBuilder( function() { return date('c'); } )
            ->datetime();
    }
}
