<?php

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $schema = new tests\UserSchema;
        ok($schema);

        $g = new LazyRecord\Schema\SchemaGenerator;
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

        $classMap = $g->generateBaseCollectionClass($schema);
        foreach( $classMap as $class => $file ) {
            ok($class);
            ok($file);
            path_ok($file);
        }
    }
}

