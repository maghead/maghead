<?php

namespace Maghead\Migration;

use Maghead\Schema\Comparator;
use Maghead\Schema\Relationship\Relationship;
use Maghead\TableParser\TableParser;
use Maghead\TableParser\ReferenceParser;
use Maghead\Connection;
use GetOptionKit\OptionResult;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use CLIFramework\Logger;
use PDO;
use LogicException;

class AutomaticMigration extends BaseMigration
{
    protected $options = null;

    public function __construct(PDO $connection, BaseDriver $driver, Logger $logger = null, OptionResult $options = null)
    {
        parent::__construct($connection, $driver, $logger);
        $this->options = $options ?: new OptionResult();
    }

    public static function options($opts)
    {
        $opts->add('no-drop-column', 'Do not drop column in automatic migration process.');
        $opts->add('separate-alter', 'Do not combine multiple alter table subquery into one alter table query.');
    }

    /**
     * @param DeclareSchema[string tableName]
     */
    public function upgrade(array $tableSchemas)
    {
        $parser = TableParser::create($this->connection, $this->driver);
        $existingTables = $parser->getTables();

        $comparator = new Comparator($this->driver);

        // Schema from runtime
        foreach ($tableSchemas as $key => $a) {
            $table = is_numeric($key) ? $a->getTable() : $key;

            $this->logger->debug("Checking table $table for schema ".get_class($a));

            if (!in_array($table, $existingTables)) {
                $this->logger->debug("Table $table does not exist, try importing...");
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $this->importSchema($a);
                continue;
            }

            $this->logger->debug("Found existing table $table");

            $b = $parser->reverseTableSchema($table, $a);

            $this->logger->debug("Comparing table `$table` with schema");
            $diffs = $comparator->compare($b, $a);

            do {
                if (count($diffs) == 0) {
                    $this->logger->debug("Nothing changed in `$table`.");
                    break;
                }

                $this->logger->debug('Found '.count($diffs).' differences');
                $alterTable = $this->alterTable($table);
                foreach ($diffs as $diff) {
                    if ($this->options->{'separate-alter'}) {
                        $alterTable = $this->alterTable($table);
                    }

                    $column = $diff->getAfterColumn();
                    switch ($diff->flag) {
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
                            throw new LogicException('afterColumn or beforeColumn is undefined.');
                        }
                        // Check primary key
                        if ($beforeColumn->primary != $afterColumn->primary) {
                            $alterTable->add()->primaryKey(['id']);
                        }
                        $alterTable->modifyColumn($afterColumn);
                        break;
                    default:
                        throw new LogicException('Unsupported flat: '.$diff->flag);
                        break;
                    }
                }
                $this->executeQuery($alterTable);
            } while (0);

            // Compare references with relationships
            if ($parser instanceof ReferenceParser) {
                $references = $parser->queryReferences($table);
                $relationships = $a->getRelations();

                $relationshipColumns = [];
                foreach ($relationships as $accessor => $rel) {
                    if ($rel['type'] !== Relationship::BELONGS_TO) {
                        continue;
                    }
                    if ($rel['foreign_schema'] == $rel['self_schema']) {
                        continue;
                    }
                    if (isset($rel['self_column']) && $rel['self_column'] == 'id') {
                        continue;
                    }

                    if (!$rel->usingIndex) {
                        continue;
                    }
                    $class = $rel['foreign_schema'];
                    $foreignSchema = new $class();
                    $foreignColumn = $foreignSchema->getColumn($rel['foreign_column']);

                    $col = $rel['self_column'];
                    $relationshipColumns[$col] = $rel;
                    if (isset($references[$col]) && preg_match('/_ibfk_/i', $references[$col]->name)) {
                        $this->logger->debug("Column {$col} foreign key {$references[$col]->name} exists");
                        continue;
                    }
                    if ($constraint = $this->builder->buildForeignKeyConstraint($rel)) {
                        $alterTable = $this->alterTable($table);
                        $add = $alterTable->add();
                        $add->foreignKey($rel['self_column']);
                        $fSchema = new $rel['foreign_schema']();
                        $alterReferences = $add->references($fSchema->getTable(), (array) $rel['foreign_column']);

                        if ($this->driver instanceof MySQLDriver) {
                            if ($act = $rel->onUpdate) {
                                $alterReferences->onUpdate($act);
                            }
                            if ($act = $rel->onDelete) {
                                $alterReferences->onDelete($act);
                            }
                        }

                        $this->executeQuery($alterTable);
                    }
                }

                // Find foreign keys that are dropped (doesn't exist in relationship)
                foreach ($references as $col => $ref) {
                    if (!preg_match('/_ibfk_/i', $ref->name)) {
                        continue;
                    }
                    if (!isset($relationshipColumns[$col])) {
                        // echo "drop {$ref->name} for ({$ref->table}, {$ref->column}) from table $table\n";
                        $alterTable = $this->alterTable($table);
                        $alterTable->dropForeignKey($ref->name);
                        $this->executeQuery($alterTable);
                    }
                }
            }
        }
    }
}
