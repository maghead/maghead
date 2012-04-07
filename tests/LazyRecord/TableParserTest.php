<?php

class TableParserTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $config = LazyRecord\ConfigLoader::getInstance();
        $config->load(true); // force load from .lazy.php
        $config->init();
        $conns = LazyRecord\ConnectionManager::getInstance();
        $mysql = $conns->getConnection('mysql');
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

