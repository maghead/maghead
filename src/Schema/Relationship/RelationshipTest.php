<?php

namespace Maghead\Schema\Relationship;

use Maghead\Schema\DeclareSchema;
use AuthorBooks\Model\AuthorSchema;
use AuthorBooks\Model\BookSchema;
use AuthorBooks\Model\AuthorBookSchema;
use AuthorBooks\Model\AddressSchema;

/**
 * @group schema
 */
class RelationshipTest extends \PHPUnit\Framework\TestCase
{
    public function testRelationshipOperation()
    {
        $r = new Relationship('books', [
                'type' => Relationship::HAS_MANY,

                'self_column' => "id",
                'self_schema' => AuthorSchema::class,

                'foreign_column' => "author_id",
                'foreign_schema' => AddressSchema::class,
        ]);

        $this->assertTrue(isset($r['type']));
        $this->assertEquals(Relationship::HAS_MANY, $r['type']);

        $schema = $r->newForeignSchema();
        $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema', $schema);

        $model = $r->newForeignModel();
        $this->assertInstanceOf('Maghead\\Runtime\\Model', $model);
    }

    public function testBelongsTo()
    {
        $rel = new BelongsTo('book', [
            'type'           => Relationship::BELONGS_TO,
            'foreign_schema' => BookSchema::class,
            'foreign_column' => 'id',
            'self_schema'    => AuthorBookSchema::class,
            'self_column'    => 'book_id',
        ]);
    }



}
