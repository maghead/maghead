<?php

namespace Maghead\Command;

use ReflectionObject;
use Maghead\Schema\DeclareSchema;
use Maghead\TableParser\TableParser;
use Maghead\Schema\Comparator;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\Comparator\ConsolePrinter as ComparatorConsolePrinter;

class DiffCommand extends BaseCommand
{
    public function brief()
    {
        return 'Compare the defined schemas with the tables in database.';
    }

    public function execute()
    {
        $formatter = new \CLIFramework\Formatter();
        $options = $this->options;
        $logger = $this->logger;

        $dsId = $this->getCurrentDataSourceId();

        $connectionManager = \Maghead\ConnectionManager::getInstance();
        $conn = $connectionManager->getConnection($dsId);
        $driver = $connectionManager->getQueryDriver($dsId);

        $this->logger->info('Performing comparison...');

        $parser = TableParser::create($conn, $driver);
        $existingTables = $parser->getTables();
        $tableSchemas = SchemaLoader::loadSchemaTableMap();

        $found = false;
        $comparator = new Comparator($driver);
        foreach ($tableSchemas as $table => $currentSchema) {
            $this->logger->debug("Checking table $table");

            $ref = new ReflectionObject($currentSchema);

            $filepath = $ref->getFilename();
            $filepath = substr($filepath, strlen(getcwd()) + 1);

            if (in_array($table, $existingTables)) {
                $before = $parser->reverseTableSchema($table, $currentSchema);
                $diffs = $comparator->compare($before, $currentSchema);
                if (count($diffs)) {
                    $found = true;
                    $printer = new ComparatorConsolePrinter($diffs);
                    $printer->beforeName = $table.":data source [$dsId]";
                    $printer->afterName = $table.':'.$filepath;
                    $printer->output();
                }
            } else {
                $msg = sprintf('+ table %-20s %s', "'".$table."'", $filepath);
                echo $formatter->format($msg, 'green') , "\n";

                $a = isset($tableSchemas[ $table ]) ? $tableSchemas[ $table ] : null;
                $diff = $comparator->compare(new DeclareSchema(), $currentSchema);
                foreach ($diff as $diffItem) {
                    echo '  ', $diffItem->toColumnAttrsString() , "\n";
                }
                $found = true;
            }
        }

        if (!$found) {
            $this->logger->info('No diff found');
        }
    }
}
