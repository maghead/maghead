<?php

namespace Maghead\Schema;

use AuthorBooks\Model\AuthorSchema;

/**
 * @group schema
 */
class MixinDeclareSchemaTest extends PHPUnit\Framework\TestCase
{
    public function testCallMixinSchemaDirectly()
    {
        $mixin = new Maghead\Schema\Mixin\MetadataMixinSchema(new AuthorSchema);
        $this->assertNotEmpty($mixin->getColumns());
        foreach ($mixin->getColumns() as $column) {
            $this->assertInstanceOf(DeclareColumn::class, $column);
            $this->assertNotNull($column->name);
        }
    }
}
