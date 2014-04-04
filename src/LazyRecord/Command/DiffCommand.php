<?php
namespace LazyRecord\Command;
use Exception;
use ReflectionClass;
use ReflectionObject;
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
        $formatter = new \CLIFramework\Formatter;
        $options = $this->options;
        $logger = $this->logger;

        $loader = ConfigLoader::getInstance();
        $loader->loadFromSymbol(true);
        $loader->initForBuild();

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();

        // XXX: from config files
        $id = $options->{'data-source'} ?: 'default';
        $conn = $connectionManager->getConnection($id);
        $driver = $connectionManager->getQueryDriver($id);


        $finder = new SchemaFinder;
        if( $paths = $loader->getSchemaPaths() ) {
            $finder->paths = $paths;
        }
        $finder->find();
        $schemas = $finder->getSchemas();


        // XXX: currently only mysql support
        $parser = \LazyRecord\TableParser\TableParser::create( $driver, $conn );
        $tableSchemas = array();
        $tables = $parser->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $parser->getTableSchema( $table );
        }

        $found = false;
        $comparator = new \LazyRecord\Schema\Comparator;
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
                $printer = new \LazyRecord\Schema\Comparator\ConsolePrinter($diff);
                $printer->beforeName = $t . ":data source [$id]";
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

