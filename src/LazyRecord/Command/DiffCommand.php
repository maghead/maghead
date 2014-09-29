<?php
namespace LazyRecord\Command;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Schema\Comparator;
use LazyRecord\Schema\Comparator\ConsolePrinter as ComparatorConsolePrinter;

class DiffCommand extends BaseCommand
{

    public function brief()
    {
        return 'diff database schema.';
    }

    public function execute()
    {
        $formatter = new \CLIFramework\Formatter;
        $options = $this->options;
        $logger = $this->logger;

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();

        // XXX: from config files
        $dsId = $this->getCurrentDataSourceId();
        
        $conn = $connectionManager->getConnection($dsId);
        $driver = $connectionManager->getQueryDriver($dsId);

        $this->logger->info('Comparing...');


        $finder = new SchemaFinder;
        if( $paths = $this->config->getSchemaPaths() ) {
            $finder->paths = $paths;
        }
        $finder->find();
        $schemas = $finder->getSchemas();


        // XXX: currently only mysql support
        $parser = TableParser::create( $driver, $conn );
        $tableSchemas = array();
        $tables = $parser->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $parser->getTableSchema( $table );
        }

        $found = false;
        $comparator = new Comparator;
        foreach( $schemas as $b ) {
            $class = $b->getModelClass();
            $ref = new ReflectionClass($class);


            $filepath = $ref->getFilename();
            $filepath = substr($filepath,strlen(getcwd()) + 1);

            $t = $b->getTable();
            if( isset( $tableSchemas[ $t ] ) ) {
                $a = $tableSchemas[ $t ];
                $diff = $comparator->compare( $a , $b );
                if ( count($diff) ) {
                    $found = true;
                }
                $printer = new ComparatorConsolePrinter($diff);
                $printer->beforeName = $t . ":data source [$dsId]";
                $printer->afterName = $t . ':' . $filepath ;
                $printer->output();
            }
            else {
                $msg = sprintf("+ table %-20s %s", "'" . $t . "'" ,$filepath);
                echo $formatter->format( $msg,'green') , "\n";


                $a = isset($tableSchemas[ $t ]) ? $tableSchemas[ $t ] : null;
                $diff = $comparator->compare( null , $b );
                foreach( $diff as $diffItem ) {
                    echo "  ", $diffItem->toColumnAttrsString() , "\n";
                }



                $found = true;
            }
        }

        if( false === $found ) {
            $this->logger->info("No diff found");
        }
    }
}

