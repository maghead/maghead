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
        $files = $finder->findByPaths(['src']);
        $this->assertNotEmpty($files);
        $expected = array(
            'src/Extensions/Localize/LocalizeMixinSchema.php',
            'src/Extensions/Revision/RevisionMixinSchema.php',
            'src/Model/MetadataSchema.php',
            'src/Model/MetadataSchemaProxy.php',
            'src/Schema/DeclareSchema.php',
            'src/Schema/DynamicSchemaDeclare.php',
            'src/Schema/Mixin/I18nSchema.php',
            'src/Schema/Mixin/MetadataMixinSchema.php',
            'src/Schema/MixinDeclareSchema.php',
            'src/Schema/RuntimeSchema.php',
            'src/Schema/SchemaDeclare.php',
            'src/Schema/TemplateSchema.php',
        );
        foreach ($expected as $e) {
            $this->assertContains($e, $files);
        }

        $schemas = SchemaLoader::loadDeclaredSchemas();
        $this->assertNotEmpty($schemas);
        foreach ($schemas as $schema) {
            $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema', $schema);
        }
    }
}
