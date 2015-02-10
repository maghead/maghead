<?php
use LazyRecord\Schema\SchemaDeclare;

class RelationshipTest extends PHPUnit_Framework_TestCase
{
    public function testRelationshipOperation()
    {
        $r = new LazyRecord\Schema\Relationship(array(
                'type' => Relationship::HAS_MANY,
                'self_column' => "id",
                'self_schema' => "TestApp\Model\AuthorSchema",
                'foreign_column' => "author_id",
                'foreign_schema' => "TestApp\Model\AddressSchema",
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

