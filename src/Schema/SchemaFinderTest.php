<?php

namespace Maghead\Schema;

use Maghead\Schema\SchemaFinder;
use Maghead\Schema\SchemaLoader;
use PHPUnit\Framework\TestCase;

/**
 * @group schema
 */
class SchemaFinderTest extends TestCase
{
    public function testSchemaFinder()
    {
        $finder = new SchemaFinder;
        $files = $finder->findByPaths(['src', 'tests']);
        $this->assertNotEmpty($files);

        $schemas = SchemaLoader::loadDeclaredSchemas();
        $this->assertNotEmpty($schemas);
        foreach ($schemas as $schema) {
            $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema', $schema);
        }
    }
}
