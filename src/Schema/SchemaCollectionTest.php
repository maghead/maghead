<?php

namespace Maghead\Schema;

use PHPUnit\Framework\TestCase;

class SchemaCollectionTest extends TestCase
{
    public function testGetBuildableSchemasShouldReturnBuildableSchemas()
    {
        $c = new SchemaCollection([
            'AuthorBooks\\Model\\AuthorSchema',
            'AuthorBooks\\Model\\BookSchema',
        ]);
        $schemas = $c->buildable();
        $this->assertCount(2, $schemas);
    }

    public function testUnique()
    {
        $c = new SchemaCollection([
            'TestApp\Model\UserSchema',
            'TestApp\Model\IDNumberSchema',
            'TestApp\Model\UserSchema',
            'TestApp\Model\IDNumberSchema',
            'TestApp\Model\NameSchema',
            'TestApp\Model\NameSchema',
            'TestApp\Model\NameSchema',
        ]);
        $c = $c->unique();
        $this->assertCount(3, $c);
    }

    public function testUniqueEvaluated()
    {
        $c = new SchemaCollection([
            'TestApp\Model\UserSchema',
            'TestApp\Model\IDNumberSchema',
            'TestApp\Model\UserSchema',
            'TestApp\Model\IDNumberSchema',
            'TestApp\Model\NameSchema',
            'TestApp\Model\NameSchema',
            'TestApp\Model\NameSchema',
        ]);
        $c = $c->evaluate()->unique();
        $this->assertCount(3, $c);
    }

    public function testEvaluateShouldCreateObjects()
    {
        $c = new SchemaCollection([
            '\TestApp\Model\UserSchema',
            '\TestApp\Model\IDNumberSchema',
            '\TestApp\Model\NameSchema',
            '\AuthorBooks\Model\AddressSchema',
            '\AuthorBooks\Model\BookSchema',
            '\AuthorBooks\Model\AuthorSchema',
            '\AuthorBooks\Model\AuthorBookSchema',
            '\AuthorBooks\Model\PublisherSchema',
        ]);

        foreach ($c as $s) {
            $this->assertInternalType('string', $s);
        }

        $ec = $c->evaluate();
        foreach ($ec as $s) {
            $this->assertTrue(is_object($s));
            $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema', $s);
        }
    }

    public function testCountable()
    {
        $collection = new SchemaCollection([
            '\TestApp\Model\UserSchema',
            '\TestApp\Model\IDNumberSchema',
            '\TestApp\Model\NameSchema',
            '\AuthorBooks\Model\AddressSchema',
            '\AuthorBooks\Model\BookSchema',
            '\AuthorBooks\Model\AuthorSchema',
            '\AuthorBooks\Model\AuthorBookSchema',
            '\AuthorBooks\Model\PublisherSchema',
        ]);
        $this->assertEquals(8, $collection->count());
    }

    public function testFilter()
    {
        $c = new SchemaCollection([
            '\TestApp\Model\UserSchema',
            '\TestApp\Model\IDNumberSchema',
            '\TestApp\Model\NameSchema',
            '\AuthorBooks\Model\AddressSchema',
            '\AuthorBooks\Model\BookSchema',
            '\AuthorBooks\Model\AuthorSchema',
            '\AuthorBooks\Model\AuthorBookSchema',
            '\AuthorBooks\Model\PublisherSchema',
        ]);

        $this->assertEquals(8, count($c));

        $rc = $c->evaluate()->filter(function ($schema) {
            return $schema instanceof \AuthorBooks\Model\BookSchema;
        });
        $this->assertCount(1, $rc);

        $expanded = $rc->expandDependency();
        $this->assertInstanceOf('Maghead\Schema\SchemaCollection', $expanded);
        $this->assertEquals(4, count($expanded));
    }
}
