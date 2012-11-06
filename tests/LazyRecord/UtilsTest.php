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

    function testSchemaUtil() 
    {
        $loader = new LazyRecord\ConfigLoader;
        ok($loader);
        $loader->loadFromSymbol(true); // force loading
        $loader->initForBuild();

        $paths = $loader->getSchemaPaths();
        ok($paths);
        ok(is_array($paths));

        $schemas = LazyRecord\Utils::getSchemaClassFromPathsOrClassNames($loader,array('tests\\UserSchema'));
        ok($schemas);


    }
}

