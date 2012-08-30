<?php
namespace LazyRecord\Migration;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\TableParser;

class MigrationGenerator
{
    public $driver;
    public $connection;
    public $migrationDir;

    function __construct($dataSourceId,$migrationDir) 
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $this->connection  = $connectionManager->getConnection($dataSourceId);
        $this->driver      = $connectionManager->getQueryDriver($dataSourceId);
        $this->migrationDir = $migrationDir;
    }


    function generate()
    {
        $parser = TableParser::create( $this->driver, $this->connection );
        $tableSchemas = array();
        $tables = $parser->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $parser->getTableSchema( $table );
        }

        $finder = new SchemaFinder;
        $finder->find();
        $schemas = $finder->getSchemas();
    }
}


