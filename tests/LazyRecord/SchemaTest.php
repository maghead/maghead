<?php

class SchemaTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->addPath( 'tests/schema' );
        $finder->find();
    }
}

