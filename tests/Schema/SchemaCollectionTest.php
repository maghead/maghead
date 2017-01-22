<?php
use Maghead\Schema\SchemaCollection;

class SchemaCollectionTest extends PHPUnit_Framework_TestCase
{
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

    public function testEvaluate() 
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

        foreach ($c->getSchemas() as $s) {
            $this->assertInternalType('string', $s);
        }

        $ec = $c->evaluate();
        foreach ($ec->getSchemas() as $s) {
            $this->assertInstanceOf('Maghead\Schema\DeclareSchema', $s);
        }
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

        $rc = $c->evaluate()->filter(function($schema) {
            return $schema instanceof \AuthorBooks\Model\BookSchema;
        });
        $this->assertEquals(1, count($rc));

        $expanded = $rc->expandDependency();
        $this->assertInstanceOf('Maghead\Schema\SchemaCollection', $expanded);
        $this->assertEquals(4, count($expanded));
    }
}

