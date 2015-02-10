<?php

class RuntimeSchemaTest extends PHPUnit_Framework_TestCase
{

    public function schemaProxyProvider()
    {
        return array( 
            array('AuthorBooks\Model\AuthorSchemaProxy'),
            array('AuthorBooks\Model\BookSchemaProxy'),
            array('AuthorBooks\Model\AuthorBookSchemaProxy'),
            array('TestApp\Model\NameSchemaProxy'),
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


    /**
     * @dataProvider schemaProxyProvider
     */
    public function testSchemaIteration($proxyClass)
    {
        $schema = new $proxyClass;
        ok($schema);
        foreach( $schema as $name => $column ) {
            ok(is_string($name));
            ok($column);
        }
    }



}

