<?php

class MixinDeclareSchemaTest extends PHPUnit_Framework_TestCase
{
    public function testCallMixinSchemaDirectly()
    {
        $mixin = new Maghead\Schema\Mixin\MetadataMixinSchema(null);
        $this->assertNotEmpty( $mixin->getColumns() );
        foreach( $mixin->getColumns() as $column ) {
            $this->assertInstanceOf('Maghead\\Schema\\DeclareColumn', $column);
            $this->assertNotNull($column->name);
        }
    }
}

