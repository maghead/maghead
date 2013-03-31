<?php
use LazyRecord\Schema\SchemaDeclare;

class RelationshipTest extends PHPUnit_Framework_TestCase
{
    public function testRelationshipOperation()
    {
        $r = new LazyRecord\Schema\Relationship(array(
                'type' => SchemaDeclare::has_many,
                'self_column' => "id",
                'self_schema' => "tests\AuthorSchema",
                'foreign_column' => "author_id",
                'foreign_schema' => "tests\AddressSchema",
        ));
        ok($r);
        ok(isset($r['type']));
        is( SchemaDeclare::has_many , $r['type'] );

        $schema = $r->newForeignSchema();
        ok($schema);

        $model = $r->newForeignModel();
        ok($model);
    }
}

