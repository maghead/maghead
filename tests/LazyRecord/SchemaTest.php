<?php
use LazyRecord\Schema\SchemaLoader;
use LazyRecord\Schema\SchemaFinder;

class SchemaTest extends PHPUnit_Framework_TestCase
{
    public function testSchemaFinder()
    {
        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->findByPaths(['tests']);
        $schemas = SchemaLoader::loadDeclaredSchemas();
        $this->assertNotEmpty($schemas);
        foreach ($schemas as $schema) {
            ok($schema);
        }
    }
}

