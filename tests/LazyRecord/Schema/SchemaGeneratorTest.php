<?php

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $schema = new tests\UserSchema;
        ok($schema);

        $g = new LazyRecord\Schema\SchemaGenerator;
        ok($g);

        list($class,$file) = $g->generateCollectionClass($schema);
        ok($class);
        ok($file);
        path_ok($file);

        list($class,$file) = $g->generateBaseCollectionClass($schema);
        ok($class);
        ok($file);
        path_ok($file);


        list($class,$file) = $g->generateBaseCollectionClass($schema);
        ok($class);
        ok($file);
        path_ok($file);
    }
}

