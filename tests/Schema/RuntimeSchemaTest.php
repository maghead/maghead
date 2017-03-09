<?php

/**
 * @group schema
 */
class RuntimeSchemaTest extends PHPUnit\Framework\TestCase
{
    public function schemaProxyProvider()
    {
        return [
            ['AuthorBooks\\Model\\AuthorSchemaProxy'],
            ['AuthorBooks\\Model\\BookSchemaProxy'],
            ['AuthorBooks\\Model\\AuthorBookSchemaProxy'],
            ['TestApp\Model\\NameSchemaProxy'],
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
