<?php

namespace Maghead\Schema;

use Maghead\Runtime\Config\Config;
use Maghead\Generator\Schema\SchemaGenerator;
use Maghead\Testing\ModelTestCase;

/**
 * @group schema
 */
class RuntimeSchemaTest extends ModelTestCase
{
    public function models()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\BookSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \TestApp\Model\NameSchema,
        ];
    }


    public function schemaProxyProvider()
    {
        return [
            ['AuthorBooks\\Model\\AuthorSchemaProxy'],
            ['AuthorBooks\\Model\\BookSchemaProxy'],
            ['AuthorBooks\\Model\\AuthorBookSchemaProxy'],
            ['TestApp\\Model\\NameSchemaProxy'],
        ];
    }

    /**
     * @dataProvider schemaProxyProvider
     */
    public function testSchemaProxyClassMethods($proxyClass)
    {
        $schema = new $proxyClass;
        foreach ($schema->getColumns() as $name => $column) {
            $this->assertNotNull($column);
            $this->assertNotNull($column->name);
            $this->assertNotNull($schema->getColumn($name));
        }
        $this->assertNotNull($schema->getTable());
        $this->assertNotNull($schema->getLabel());
    }


    /**
     * @dataProvider schemaProxyProvider
     */
    public function testSchemaIteration($proxyClass)
    {
        $schema = new $proxyClass;
        foreach ($schema as $name => $column) {
            $this->assertTrue(is_string($name));
            $this->assertNotNull($column);
        }
    }
}
