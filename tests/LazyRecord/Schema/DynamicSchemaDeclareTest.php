<?php

class DynamicSchemaDeclareTest extends PHPUnit_Framework_TestCase
{

    public function testWineSchemaFromDynamicSchemaDeclare()
    {
        $wine = new \tests\Wine;
        ok($wine);


        // create schema object from the schema method
        $schema = new LazyRecord\Schema\DynamicSchemaDeclare( $wine );
        ok($schema);
        return $schema;
    }


    /**
     * @depends testWineSchemaFromDynamicSchemaDeclare
     */
    public function testTableMethod($schema)
    {
        is('wines',$schema->getTable());
    }

    /**
     * @depends testWineSchemaFromDynamicSchemaDeclare
     */
    public function testModelClassMethod($schema)
    {
        is('tests\Wine',$schema->getModelClass());
    }


    /**
     * @depends testWineSchemaFromDynamicSchemaDeclare
     */
    public function testModelNameMethod($schema)
    {
        is('Wine',$schema->getModelName());
    }

    /**
     * @depends testWineSchemaFromDynamicSchemaDeclare
     */
    function testGetBaseModelClass($schema) 
    {
        is('tests\WineBase',$schema->getBaseModelClass() );
    }

    /**
     * @depends testWineSchemaFromDynamicSchemaDeclare
     */
    function testGetNamespace($schema)
    {
        is('tests',$schema->getNamespace());
    }

    /**
     * @depends testWineSchemaFromDynamicSchemaDeclare
     */
    function testColumns($schema)
    {
        ok( $schema->getColumn('name') );
        ok( $schema->getColumn('years') );
    }


    function testSchemaFinder()
    {
        $schemas = LazyRecord\ClassUtils::get_declared_dynamic_schema_classes_from_models();
        ok($schemas);
        foreach($schemas as $schema ) {
            ok( $schema instanceof \LazyRecord\Schema\DynamicSchemaDeclare );
            ok($schema->getModelClass() );
        }
    }
}

