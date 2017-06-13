<?php

namespace Maghead\TableBuilder;

use InvalidArgumentException;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Driver\SQLiteDriver;

use Magsql\ArgumentArray;
use Magsql\Universal\Query\CreateIndexQuery;
use Magsql\Universal\Syntax\Constraint;
use Maghead\Schema\DynamicSchemaDeclare;
use Maghead\Schema\Schema;
use Maghead\Runtime\Model;
use Maghead\Schema\DeclareColumn;

use Maghead\Schema\Relationship\Relationship;
use Maghead\Schema\Relationship\HasOne;
use Maghead\Schema\Relationship\BelongsTo;
use Maghead\Schema\Relationship\HasMany;
use Maghead\Schema\Relationship\ManyToMany;

abstract class TableBuilder
{
    protected $rebuild;

    protected $clean;

    protected $driver;

    public function __construct(BaseDriver $driver, array $options = array())
    {
        $this->driver = $driver;
        if (isset($options['rebuild'])) {
            $this->rebuild = $options['rebuild'];
        }
        if (isset($options['clean'])) {
            $this->clean = $options['clean'];
        }
    }

    abstract public function buildColumn(Schema $schema, DeclareColumn $column);

    public function setClean($clean = true)
    {
        $this->clean = true;
    }

    public function setRebuild($rebuild = true)
    {
        $this->rebuild = $rebuild;
    }

    public function prepare()
    {
        return [];
    }

    public function finalize()
    {
        return [];
    }

    public function createTable(Schema $schema)
    {
        $sql = 'CREATE TABLE '
            .$this->driver->quoteIdentifier($schema->getTable())." ( \n";

        $columnSqls = array();
        foreach ($schema->columns as $name => $column) {
            if ($column->virtual) {
                continue;
            }
            $columnSqls[] = '  '.$this->buildColumn($schema, $column);
        }
        $referencesSqls = $this->buildForeignKeys($schema);
        $sql .= implode(",\n", array_merge($columnSqls, $referencesSqls));

        $sql .= "\n);\n";

        return $sql;
    }

    public function build(Schema $schema)
    {
        if ($schema instanceof Model) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
        }
        $sqls = [];
        $tableSqls = $this->buildTable($schema);
        $sqls = array_merge($sqls, $tableSqls);

        $indexSqls = $this->buildIndex($schema);
        $sqls = array_merge($sqls, $indexSqls);

        return $sqls;
    }

    public function buildTable(Schema $schema)
    {
        $sqls = array();
        if ($this->clean || $this->rebuild) {
            $sqls[] = $this->dropTable($schema);
        }
        if ($this->clean) {
            return $sqls;
        }
        $sqls[] = $this->createTable($schema);

        return $sqls;
    }

    public function buildIndex(Schema $schema)
    {
        // build single column index
        $sqls = array();
        foreach ($schema->columns as $name => $column) {
            if ($column->index) {
                $table = $schema->getTable();
                $indexName = is_string($column->index) ? $column->index
                    : 'idx_'.$table.'_'.$name;
                $query = new CreateIndexQuery($indexName);
                $query->on($table, [$name]);
                if ($column->index_using) {
                    $query->using($column->index_using);
                }
                $sqls[] = $query->toSql($this->driver, new ArgumentArray());
            }
        }
        if ($queries = $schema->getIndexQueries()) {
            foreach ($queries as $query) {
                $sqls[] = $query->toSql($this->driver, new ArgumentArray());
            }
        }

        return $sqls;
    }

    public function buildForeignKeyConstraint(Relationship $rel)
    {
        $constraint = new Constraint();
        $constraint->foreignKey($rel['self_column']);

        $fSchema = new $rel['foreign_schema']();
        $references = $constraint->references($fSchema->getTable(), (array) $rel['foreign_column']);

        return $constraint;
    }

    public function buildForeignKeys(Schema $schema)
    {
        $sqls = [];
        foreach ($schema->relations as $rel) {
            if ($rel instanceof BelongsTo
                || $rel instanceof HasMany
                || $rel instanceof HasOne
            ) {
                if ($rel['foreign_schema'] == $rel['self_schema']) {
                    continue;
                }
                if (isset($rel['self_column']) && $rel['self_column'] != 'id') {
                    if ($constraint = $this->buildForeignKeyConstraint($rel)) {
                        $sqls[] = $constraint->toSql($this->driver, new ArgumentArray());
                    }
                }
            }
        }

        return $sqls;
    }

    public static function create(BaseDriver $driver, array $options = array())
    {
        if ($driver instanceof MySQLDriver) {
            return new MysqlBuilder($driver, $options);
        } elseif ($driver instanceof PgSQLDriver) {
            return new PgsqlBuilder($driver, $options);
        } elseif ($driver instanceof SQLiteDriver) {
            return new SqliteBuilder($driver, $options);
        }
        throw new InvalidArgumentException('Unsupported driver.');
    }


}
