<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\SchemaLoader;
use PHPUnit_Framework_TestCase;

class SchemaFinderTest extends PHPUnit_Framework_TestCase
{
    public function testSchemaFinder()
    {
        $finder = new SchemaFinder;
        $finder->findByPaths(['src', 'tests']);

        $schemas = SchemaLoader::loadDeclaredSchemas();
        $this->assertNotEmpty($schemas);
        foreach ($schemas as $schema) {
            $this->assertInstanceOf('LazyRecord\\Schema\\DeclareSchema',$schema);
        }
    }


}
