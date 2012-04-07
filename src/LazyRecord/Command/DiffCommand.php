<?php
namespace LazyRecord\Command;
use Exception;
use ReflectionClass;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;

class DiffCommand extends Command
{

    public function brief()
    {
        return 'diff database schema.';
    }

    public function options($opts)
    {
        // --data-source
        $opts->add('D|data-source:', 'specify data source id');
    }

    public function execute()
    {
        $options = $this->options;
        $logger = $this->logger;

        $loader = ConfigLoader::getInstance();
        $loader->load();
        $loader->initForBuild();


        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $logger->info("Initialize connection manager...");

        // XXX: from config files
        $id = $options->{'data-source'} ?: 'default';
        $conn = $connectionManager->getConnection($id);
        $driver = $connectionManager->getQueryDriver($id);


        $finder = new SchemaFinder;
        if( $paths = $loader->getSchemaPaths() ) {
            $finder->paths = $paths;
        }
        $finder->loadFiles();
        $classes = $finder->getSchemaClasses();


        // XXX: currently only mysql support
        $parser = \LazyRecord\TableParser::create( $driver, $conn );
        $tableSchemas = array();
        $tables = $parser->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $parser->getTableSchema( $table );
        }

        $found = false;
        $comparator = new \LazyRecord\Schema\Comparator;
        foreach( $classes as $class ) {
            $b = new $class;
            $ref = new ReflectionClass($class);
            $t = $b->getTable();
            if( isset( $tableSchemas[ $t ] ) ) {
                $a = $tableSchemas[ $t ];
                $diff = $comparator->compare( $a , $b );
                if( count($diff) ) 
                    $found = true;
                $printer = new \LazyRecord\Schema\Comparator\ConsolePrinter($diff);
                $printer->beforeName = $t;
                $printer->afterName = $class . ':' . $ref->getFilename() ;
                $printer->output();
            }
            else {
                echo "New table: " , $class , ':' , $ref->getFilename() , "\n";
                $found = true;
            }
        }

        if( false === $found ) {
            $this->logger->info("No diff found");
        }
    }
}

