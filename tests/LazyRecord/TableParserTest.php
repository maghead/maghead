<?php

class TableParserTest extends PHPUnit_Framework_TestCase
{
    function testDrivers()
    {
        $types = array();

        $config = LazyRecord\ConfigLoader::getInstance();
        $config->loadFromSymbol(true); // force load from .lazy.php
        $config->init();


        $conns = LazyRecord\ConnectionManager::getInstance();
        if ( $conns->hasDataSource('mysql') && extension_loaded('pdo_mysql') ) {
            $this->runDriverTest('mysql');
        }
        if ( $conns->hasDataSource('pgsql') && extension_loaded('pdo_pgsql') ) {
            $this->runDriverTest('pgsql');
        }
        if ( $conns->hasDataSource('sqlite') && extension_loaded('pdo_sqlite') ) {
            $this->runDriverTest('sqlite');
        }
    }


    /**
     * @dataProvider getDrivers
     */
    public function runDriverTest($driverType)
    {
        $conns = LazyRecord\ConnectionManager::getInstance();
        $conn   = $conns->getConnection($driverType);
        $driver = $conns->getQueryDriver($driverType);
        $parser = LazyRecord\TableParser\TableParser::create($driver,$conn);
        ok( $parser );

        $tables = $parser->getTables();
        ok($tables, "Fetch table by $driverType");
        foreach(  $tables as $table ) {
            ok( $table );
            $schema = $parser->getTableSchema( $table );

            ok( $schema );
            ok( $schema->getColumns() );
        }
    }
}

