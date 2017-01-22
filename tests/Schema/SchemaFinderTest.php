<?php
namespace Maghead\Schema;
use Maghead\Schema\SchemaFinder;
use Maghead\Schema\SchemaLoader;
use PHPUnit_Framework_TestCase;

class SchemaFinderTest extends PHPUnit_Framework_TestCase
{
    public function testSchemaFinder()
    {
        $finder = new SchemaFinder;
        $finder->findByPaths(['src', 'tests']);

        $schemas = SchemaLoader::findDeclaredSchemas();
        $this->assertNotEmpty($schemas);
        foreach ($schemas as $schema) {
            $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema',$schema);
        }
    }


}
