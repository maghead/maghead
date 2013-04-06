<?php

class SchemaTest extends PHPUnit_Framework_TestCase
{
    public function testSchemaFinder()
    {
        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->addPath( 'tests/schema' );
        ok( $finder->find() );
    }
}

