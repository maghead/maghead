<?php

class ExecutiveQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $counter = 0;
        $query = new \LazyRecord\ExecutiveQueryBuilder;
        $query->driver = new \LazyRecord\QueryDriver;
        $query->callback = function($builder,$sql) use (& $counter) {
            $counter++;
        };
        $query->select('*');
        $query->execute();

        is( 1 , $counter );
    }
}

