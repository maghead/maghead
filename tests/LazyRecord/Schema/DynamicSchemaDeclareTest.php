<?php

class DynamicSchemaDeclareTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $wine = new \tests\Wine;
        ok($wine);

        $schema = new LazyRecord\Schema\DynamicSchemaDeclare( $wine );

    }
}

