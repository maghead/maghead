<?php
namespace LazyRecord\Migration;
use LazyRecord\Console;
use LazyRecord\Metadata;
use LazyRecord\Schema\Comparator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\ConnectionManager;
use LazyRecord\Connection;
use LazyRecord\QueryDriver;
use LazyRecord\Migration\Migratable;
use GetOptionKit\OptionResult;

class AutomaticMigration extends Migration implements Migratable
{
    protected $options = null;

    public function __construct($dsId, OptionResult $options = null) {
        $this->options = $options ?: new OptionResult;
        parent::__construct($dsId);
    }
    
    public function upgrade()
    {
        $parser = TableParser::create($this->driver, $this->connection);

        $tableSchemas = $parser->getTableSchemaMap();
        $comparator = new Comparator;

        $existingTables = $parser->getTables();

        // schema from runtime
        foreach ($tableSchemas as $t => $after) {
            $this->logger->debug("Checking table $t for schema " . get_class($after));

            $foundTable = in_array($t, $existingTables);
            if ($foundTable) {

                $this->logger->debug("Found existing table $t");

                $before = $parser->reverseTableSchema($t);

                $this->logger->debug("Comparing table $t with schema");
                $diffs = $comparator->compare($before , $after);

                if (count($diffs)) {
                    $this->logger->debug("Found " . count($diffs) . ' differences');
                }

                foreach ($diffs as $diff) {
                    $column = $diff->getAfterColumn();

                    switch($diff->flag) {
                    case '+':
                        $this->addColumn($t , $column);
                        break;
                    case '-':
                        if ($this->options->{'no-drop-column'}) {
                            continue;
                        }
                        $this->dropColumn($t, $diff->name);
                        break;
                    case '=':
                        if ($afterColumn = $diff->getAfterColumn()) {
                            $this->modifyColumn($t, $afterColumn);
                        } else {
                            throw new \Exception("afterColumn is undefined.");
                        }
                        break;
                    default:
                        $this->logger->warn("** unsupported flag: " . $diff->flag);
                        break;
                    }
                }
            } else {
                $this->logger->debug("Table $t not found, importing schema...");
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $this->importSchema($after);
            }
        }
    }


}






