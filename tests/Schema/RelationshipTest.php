<?php
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Relationship\Relationship;

/**
 * @group schema
 */
class RelationshipTest extends PHPUnit\Framework\TestCase
{
    public function testRelationshipOperation()
    {
        $r = new Maghead\Schema\Relationship\Relationship('books', array(
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
        $this->assertInstanceOf('Maghead\\Runtime\\BaseModel', $model);
    }
}
