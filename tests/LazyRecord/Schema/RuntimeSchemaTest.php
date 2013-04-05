<?php

class RuntimeSchemaTest extends PHPUnit_Framework_TestCase
{

    public function schemaProxyProvider()
    {
        return array( 
            array('tests\AuthorSchemaProxy'),
            array('tests\BookSchemaProxy'),
            array('tests\AuthorBookSchemaProxy'),
            array('tests\NameSchemaProxy'),
        );
    }

    /**
     * @dataProvider schemaProxyProvider
     */
    public function testSchemaProxyClassMethods($proxyClass)
    {
        $schema = new $proxyClass;
        ok($schema);
        foreach( $schema->getColumns() as $name => $column ) {
            ok($name);
            ok($column);
            ok($column->name);
            ok($schema->getColumn($name));
        }
        ok($schema->getTable());
        ok($schema->getLabel());
    }
}

