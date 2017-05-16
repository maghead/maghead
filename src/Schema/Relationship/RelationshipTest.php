<?php

namespace Maghead\Schema\Relationship;

use Maghead\Schema\DeclareSchema;

/**
 * @group schema
 */
class RelationshipTest extends \PHPUnit\Framework\TestCase
{
    public function testRelationshipOperation()
    {
        $r = new Relationship('books', array(
                'type' => Relationship::HAS_MANY,
                'self_column' => "id",
                'self_schema' => "AuthorBooks\Model\AuthorSchema",
                'foreign_column' => "author_id",
                'foreign_schema' => "AuthorBooks\Model\AddressSchema",
        ));
        $this->assertTrue(isset($r['type']));
        $this->assertEquals(Relationship::HAS_MANY, $r['type']);

        $schema = $r->newForeignSchema();
        $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema', $schema);

        $model = $r->newForeignModel();
        $this->assertInstanceOf('Maghead\\Runtime\\Model', $model);
    }
}
