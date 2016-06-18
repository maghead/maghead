<?php

namespace LazyRecord\Migration;

use LazyRecord\Schema\Comparator;
use LazyRecord\Schema\Relationship\Relationship;
use LazyRecord\TableParser\TableParser;
use LazyRecord\TableParser\ReferenceParser;
use LazyRecord\Connection;
use GetOptionKit\OptionResult;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use PDO;
use LogicException;

class AutomaticMigration extends Migration implements Migratable
{
    protected $options = null;

    public function __construct(BaseDriver $driver, PDO $connection, OptionResult $options = null)
    {
        $this->options = $options ?: new OptionResult();
        parent::__construct($driver, $connection);
    }

    public function upgrade()
    {
        $parser = TableParser::create($this->driver, $this->connection);

        $tableSchemas = $parser->getDeclareSchemaMap();

        $comparator = new Comparator($this->driver);
        $existingTables = $parser->getTables();

        // Schema from runtime
        foreach ($tableSchemas as $table => $schema) {
            $this->logger->debug("Checking table $table for schema ".get_class($schema));

            if (!in_array($table, $existingTables)) {
                $this->logger->debug("Table $table does not exist, try importing...");
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $this->importSchema($schema);
                continue;
            }

            $this->logger->debug("Found existing table $table");

            $before = $parser->reverseTableSchema($table, $schema);

            $this->logger->debug("Comparing table `$table` with schema");
            $diffs = $comparator->compare($before, $schema);

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
                $relationships = $schema->getRelations();

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
