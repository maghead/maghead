<?php

class MixinDeclareSchemaTest extends PHPUnit_Framework_TestCase
{
    public function testCallMixinSchemaDirectly()
    {
        $mixin = new LazyRecord\Schema\Mixin\MetadataMixinSchema(null);
        $this->assertNotEmpty( $mixin->getColumns() );
        foreach( $mixin->getColumns() as $column ) {
            $this->assertInstanceOf('LazyRecord\\Schema\\ColumnDeclare', $column);
            $this->assertNotNull($column->name);
        }
    }
}

