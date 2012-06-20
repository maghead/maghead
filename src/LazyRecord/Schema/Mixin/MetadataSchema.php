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
            ->default( function() { return date('c'); } )
            ->timestamp();

        $this->column('created_on')
            ->isa('DateTime')
            ->default( function() { return date('c'); } )
            ->timestamp();
    }
}
