<?php
namespace tests;
use Lazy\Schema\SchemaDeclare;
use Lazy\Schema;

class MetadataMixinSchema extends \Lazy\Schema\MixinSchemaDeclare
{

    function schema()
    {
        $this->column('created_on')
            ->isa('DateTime')
            ->defaultBuilder( function() { return date('c'); } )
            ->timestamp();
    }
}
