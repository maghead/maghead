<?php

class ExecutiveQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $counter = 0;
        $query = new \Lazy\ExecutiveQueryBuilder;
        $query->driver = new \Lazy\QueryDriver;
        $query->callback = function($builder,$sql) use (& $counter) {
            $counter++;
        };
        $query->select('*');
        $query->execute();

        is( 1 , $counter );
    }
}

