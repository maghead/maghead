<?php

class DynamicSchemaDeclareTest extends PHPUnit_Framework_TestCase
{
    function testSchema()
    {
        $wine = new \tests\Wine;
        ok($wine);
        $schema = new LazyRecord\Schema\DynamicSchemaDeclare( $wine );
        ok($schema);
        return $schema;
    }


    /**
     * @depends testSchema
     */
    function testTable($schema)
    {
        is('wines',$schema->getTable());
    }

    /**
     * @depends testSchema
     */
    function testModelClass($schema)
    {
        is('tests\Wine',$schema->getModelClass());
    }


    /**
     * @depends testSchema
     */
    function testModelName($schema)
    {
        is('Wine',$schema->getModelName());
    }

    /**
     * @depends testSchema
     */
    function testGetBaseModelClass($schema) 
    {
        is('tests\WineBase',$schema->getBaseModelClass() );
    }

    /**
     * @depends testSchema
     */
    function testGetNamespace($schema)
    {
        is('tests',$schema->getNamespace());
    }

    /**
     * @depends testSchema
     */
    function testColumns($schema)
    {
        ok( $schema->getColumn('name') );
        ok( $schema->getColumn('years') );
    }

}

