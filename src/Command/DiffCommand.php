<?php
namespace LazyRecord\Command;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\SchemaUtils;
use LazyRecord\ConfigLoader;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Schema\Comparator;
use LazyRecord\Schema\Comparator\ConsolePrinter as ComparatorConsolePrinter;

class DiffCommand extends BaseCommand
{

    public function brief()
    {
        return 'Compare the defined schemas with the tables in database.';
    }

    public function execute()
    {
        $formatter = new \CLIFramework\Formatter;
        $options = $this->options;
        $logger = $this->logger;

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();

        $dsId = $this->getCurrentDataSourceId();
        
        $conn = $connectionManager->getConnection($dsId);
        $driver = $connectionManager->getQueryDriver($dsId);

        $this->logger->info('Performing Comparison...');

        $parser = TableParser::create($driver, $conn);
        $existingTables = $parser->getTables();
        $tableSchemas = $parser->getDeclareSchemaMap();

        $found = false;
        $comparator = new Comparator($driver);
        foreach ($tableSchemas as $table => $currentSchema) {
            $this->logger->debug("Checking table $table");

            $ref = new ReflectionObject($currentSchema);

            $filepath = $ref->getFilename();
            $filepath = substr($filepath,strlen(getcwd()) + 1);

            if (in_array($table, $existingTables)) {
                $before = $parser->reverseTableSchema($table);
                $diffs = $comparator->compare($before, $currentSchema);
                if (count($diffs)) {
                    $found = true;
                    $printer = new ComparatorConsolePrinter($diffs);
                    $printer->beforeName = $table . ":data source [$dsId]";
                    $printer->afterName = $table . ':' . $filepath ;
                    $printer->output();
                }
            } else {
                $msg = sprintf("+ table %-20s %s", "'" . $table . "'" ,$filepath);
                echo $formatter->format( $msg,'green') , "\n";

                $a = isset($tableSchemas[ $table ]) ? $tableSchemas[ $table ] : null;
                $diff = $comparator->compare(new DeclareSchema, $currentSchema);
                foreach( $diff as $diffItem ) {
                    echo "  ", $diffItem->toColumnAttrsString() , "\n";
                }
                $found = true;
            }
        }

        if (!$found) {
            $this->logger->info("No diff found");
        }
    }
}

