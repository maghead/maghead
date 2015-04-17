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
use SQLBuilder\Driver\BaseDriver;
use PDO;

class AutomaticMigration extends Migration implements Migratable
{
    protected $options = null;

    public function __construct(BaseDriver $driver, PDO $connection, OptionResult $options = null) {
        $this->options = $options ?: new OptionResult;
        parent::__construct($driver, $connection);
    }
    
    public function upgrade()
    {
        $parser = TableParser::create($this->driver, $this->connection);

        $tableSchemas = $parser->getDeclareSchemaMap();

        $comparator = new Comparator;
        $existingTables = $parser->getTables();

        // schema from runtime
        foreach ($tableSchemas as $table => $schema) {
            $this->logger->debug("Checking table $table for schema " . get_class($schema));

            $foundTable = in_array($table, $existingTables);
            if ($foundTable) {
                $this->logger->debug("Found existing table $table");

                $before = $parser->reverseTableSchema($table);

                $this->logger->debug("Comparing table $table with schema");
                $diffs = $comparator->compare($before , $schema);

                if (count($diffs)) {
                    $this->logger->debug("Found " . count($diffs) . ' differences');
                }

                foreach ($diffs as $diff) {
                    $column = $diff->getAfterColumn();

                    switch($diff->flag) {
                    case '+':
                        $this->addColumn($table, $column);
                        break;
                    case '-':
                        if ($this->options->{'no-drop-column'}) {
                            continue;
                        }
                        $this->dropColumn($table, $diff->name);
                        break;
                    case '=':
                        if ($afterColumn = $diff->getAfterColumn()) {
                            $beforeColumn = $diff->getBeforeColumn();

                            // check foreign key change
                            if ($beforeColumn->primary != $afterColumn->primary) {
                                $alterTable = $this->alterTable($table;
                                $alterTable->add()->primaryKey(['id']);
                            }

                            $this->modifyColumn($table, $afterColumn);
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
                $this->logger->debug("Table $table not found, importing schema...");
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $this->importSchema($schema);
            }
        }
    }


}






