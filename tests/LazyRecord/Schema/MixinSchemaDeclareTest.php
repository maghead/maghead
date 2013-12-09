<?php

class MixinSchemaDeclareTest extends PHPUnit_Framework_TestCase
{
    public function testCallMixinSchemaDirectly()
    {
        $mixin = new LazyRecord\Schema\Mixin\MetadataSchema;
        ok($mixin);

        ok( $mixin->getColumns() );
        foreach( $mixin->getColumns() as $column ) {
            isa_ok('LazyRecord\\Schema\\SchemaDeclare\\Column', $column);
            ok($column->name);
        }
    }
}

