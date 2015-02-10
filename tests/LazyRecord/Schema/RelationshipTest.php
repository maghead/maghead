<?php
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\Relationship;

class RelationshipTest extends PHPUnit_Framework_TestCase
{
    public function testRelationshipOperation()
    {
        $r = new LazyRecord\Schema\Relationship(array(
                'type' => Relationship::HAS_MANY,
                'self_column' => "id",
                'self_schema' => "AuthorBooks\Model\AuthorSchema",
                'foreign_column' => "author_id",
                'foreign_schema' => "AuthorBooks\Model\AddressSchema",
        ));
        ok($r);
        ok(isset($r['type']));
        is( Relationship::HAS_MANY , $r['type'] );

        $schema = $r->newForeignSchema();
        ok($schema);

        $model = $r->newForeignModel();
        ok($model);
    }
}

