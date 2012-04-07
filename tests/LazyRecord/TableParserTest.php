<?php

class TableParserTest extends PHPUnit_Framework_TestCase
{

    function setUp()
    {
    }

    function getDrivers()
    {
        $types = array();
        $config = LazyRecord\ConfigLoader::getInstance();
        $config->load(true); // force load from .lazy.php
        $config->init();
        $conns = LazyRecord\ConnectionManager::getInstance();

        if( $conns->hasDataSource('mysql') )
            $types[] = array( 'mysql' );
        if( $conns->hasDataSource('pgsql') )
            $types[] = array( 'pgsql' );
        return $types;
    }


    /**
     * @dataProvider getDrivers
     */
    function test($driverType)
    {
        $conns = LazyRecord\ConnectionManager::getInstance();
        $conn = $conns->getConnection($driverType);
        $driver = $conns->getQueryDriver($driverType);
        $parser = LazyRecord\TableParser::create($driver,$conn);
        ok( $parser );

        $tables = $parser->getTables();
        ok( $tables );
        foreach(  $tables as $table ) {
            ok( $table );
            $schema = $parser->getTableSchema( $table );

            ok( $schema );
            ok( $schema->getColumns() );
        }
    }
}

