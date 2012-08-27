<?php

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $schema = new tests\UserSchema;
        ok($schema);

        $g = new LazyRecord\Schema\SchemaGenerator;
        $g->setLogger( new TestLogger );
        ok($g);

        $classMap = $g->generateCollectionClass($schema);
        foreach( $classMap as $class => $file ) {
            ok($class);
            ok($file);
            path_ok($file);
        }

        $classMap = $g->generateBaseCollectionClass($schema);
        foreach( $classMap as $class => $file ) {
            ok($class);
            ok($file);
            path_ok($file);
        }

        $classMap = $g->generateSchemaProxyClass($schema);
        foreach( $classMap as $class => $file ) {
            ok($class);
            ok($file);
            path_ok($file);
        }

        $classMap = $g->generate(array($schema));
        ok($classMap);

        foreach( $classMap as $class => $file ) {
            ok($class);
            ok($file);
            path_ok($file);
            require $file;
        }

        ok( isset($classMap['\tests\UserSchemaProxy']) );
        ok( isset($classMap['\tests\User']) );
        ok( isset($classMap['\tests\UserBase']) );
        ok( isset($classMap['\tests\UserCollection']) );
        ok( isset($classMap['\tests\UserCollectionBase']) );

        $schemaProxy = new \tests\UserSchemaProxy;
        ok($schemaProxy);
    }
}

