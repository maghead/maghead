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


    function generate($schemas)
    {
        $parser = TableParser::create( $this->driver, $this->connection );
        $tableSchemas = array();

        // database schemas
        $tables = $parser->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $parser->getTableSchema( $table );
        }

        $comparator = new \LazyRecord\Schema\Comparator;
        // schema from runtime
        foreach( $schemas as $b ) {
            $t = $b->getTable();
            $foundTable = isset( $tableSchemas[ $t ] );
            if( $foundTable ) {
                $a = $tableSchemas[ $t ];
                $diff = $comparator->compare( $a , $b );
                // generate alter table statement.

                print_r($diff);

            } else {
                // generate create table statement.
                // use sqlbuilder to build schema sql

            }
        }
    }
}


