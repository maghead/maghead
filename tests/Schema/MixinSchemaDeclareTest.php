<?php

/**
 * @group schema
 */
class MixinDeclareSchemaTest extends PHPUnit\Framework\TestCase
{
    public function testCallMixinSchemaDirectly()
    {
        $mixin = new Maghead\Schema\Mixin\MetadataMixinSchema(null);
        $this->assertNotEmpty($mixin->getColumns());
        foreach ($mixin->getColumns() as $column) {
            $this->assertInstanceOf('Maghead\\Schema\\DeclareColumn', $column);
            $this->assertNotNull($column->name);
        }
    }
}
