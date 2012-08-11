<?php

class UtilsTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $params = LazyRecord\Utils::breakDSN('pgsql:host=localhost;dbname=lazy_test');
        is( 'pgsql' , $params['driver'] );
        is( 'localhost' , $params['host'] );
        is( 'lazy_test' , $params['dbname'] );
    }

    function testSqliteMem()
    {
        $params = LazyRecord\Utils::breakDSN('sqlite::memory:');
        is( 'sqlite' , $params['driver'] );
        ok( $params[':memory:'] );
    }

    function testEvaluate()
    {
        is( 1, LazyRecord\Utils::evaluate(1) );
        is( 2, LazyRecord\Utils::evaluate( function() { return 2; }) );
    }
}

