<?php
namespace LazyRecord\Migration;
use LazyRecord\Console;
use LazyRecord\Metadata;
use LazyRecord\Schema\Comparator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\TableParser\ReferenceParser;
use LazyRecord\ConnectionManager;
use LazyRecord\Connection;
use LazyRecord\QueryDriver;
use LazyRecord\Migration\Migratable;
use GetOptionKit\OptionResult;
use SQLBuilder\Driver\BaseDriver;
use PDO;
use LogicException;
use Exception;

class AutomaticMigration extends Migration implements Migratable
{
    protected $options = null;

    public function __construct(BaseDriver $driver, PDO $connection, OptionResult $options = null)
    {
        $this->options = $options ?: new OptionResult;
        parent::__construct($driver, $connection);
    }
    
    public function upgrade()
    {
        $parser = TableParser::create($this->driver, $this->connection);

        $tableSchemas = $parser->getDeclareSchemaMap();

        $comparator = new Comparator($this->driver);
        $existingTables = $parser->getTables();

        // schema from runtime
        foreach ($tableSchemas as $table => $schema) {

            if ($parser instanceof ReferenceParser) {
                $references = $parser->queryReference($table);
            }

            $this->logger->debug("Checking table $table for schema " . get_class($schema));

            if (!in_array($table, $existingTables)) {
                $this->logger->debug("Table $table does not exist, try importing...");
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $this->importSchema($schema);
                continue;
            }

            $this->logger->debug("Found existing table $table");


            $before = $parser->reverseTableSchema($table);

            $this->logger->debug("Comparing table `$table` with schema");
            $diffs = $comparator->compare($before , $schema);

            if (count($diffs) == 0) {
                $this->logger->debug("Nothing changed in `$table`.");
                continue;
            }

            $this->logger->debug("Found " . count($diffs) . ' differences');

            $alterTable = $this->alterTable($table);
            foreach ($diffs as $diff) {
                if ($this->options->{'separate-alter'}) {
                    $alterTable = $this->alterTable($table);
                }

                $column = $diff->getAfterColumn();
                switch($diff->flag) {
                case 'A':
                    $alterTable->addColumn($column);
                    break;

                case 'D':
                    if ($this->options->{'no-drop-column'}) {
                        continue;
                    }
                    $alterTable->dropColumnByName($diff->name);
                    break;

                case 'M':
                    $afterColumn = $diff->getAfterColumn();
                    $beforeColumn = $diff->getBeforeColumn();
                    if (!$afterColumn || !$beforeColumn) {
                        throw new LogicException("afterColumn or beforeColumn is undefined.");
                    }

                    // check foreign key change
                    if ($beforeColumn->primary != $afterColumn->primary) {
                        $alterTable->add()->primaryKey(['id']);
                    }

                    $alterTable->modifyColumn($afterColumn);
                    break;
                default:
                    throw new LogicException("Unsupported flat: " . $diff->flag);
                    break;
                }
            }
            $this->executeQuery($alterTable);
        }
    }
}
