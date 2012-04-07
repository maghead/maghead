<?php

class TableParserTest extends PHPUnit_Framework_TestCase
{
    function testMysql()
    {
        $config = LazyRecord\ConfigLoader::getInstance();
        $config->load(true); // force load from .lazy.php
        $config->init();
        $conns = LazyRecord\ConnectionManager::getInstance();

        if( ! isset($conns['mysql']) )
            return;

        $mysql = $conns->getConnection('mysql2');

        $driver = $conns->getQueryDriver('mysql');
        $parser = LazyRecord\TableParser::create($driver,$mysql);
        ok( $parser );

        $tables = $parser->getTables();
        ok( $tables );
        foreach(  $tables as $table ) {
            ok( $table );
            $schema = $parser->getTableSchema( $table );

            ok( $schema );
        }
    }
}

