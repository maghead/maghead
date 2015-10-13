<?php
use LazyRecord\Schema\SchemaFinder;

class SchemaFinderTest extends PHPUnit_Framework_TestCase
{
    public function testFinder()
    {
        $finder = new SchemaFinder;
        $files = $finder->findByPaths(['src']);
    }


}
