<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema;

class MetadataMixinSchema extends \LazyRecord\Schema\MixinSchemaDeclare
{

    function schema()
    {
        $this->column('created_on')
            ->isa('DateTime')
            ->defaultBuilder( function() { return date('c'); } )
            ->timestamp();
    }
}
