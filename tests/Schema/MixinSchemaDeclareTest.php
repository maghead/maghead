<?php

namespace Maghead\Schema;

use AuthorBooks\Model\AuthorSchema;
use PHPUnit\Framework\TestCase;

/**
 * @group schema
 */
class MixinDeclareSchemaTest extends TestCase
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
