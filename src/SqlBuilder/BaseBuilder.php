<?php
namespace LazyRecord\SqlBuilder;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\SQLiteDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateIndexQuery;
use SQLBuilder\Universal\Syntax\Constraint;

use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\TemplateSchema;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;
use LazyRecord\BaseModel;
use LazyRecord\Schema\DeclareColumn;

abstract class BaseBuilder
{
    public $rebuild;

    public $clean;

    public $driver;

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

    abstract public function buildColumnSql(SchemaInterface $schema, DeclareColumn $column);

    public function prepare() { return []; }

    public function finalize() { return []; }

    public function createTable(SchemaInterface $schema)
    {
        $sql = 'CREATE TABLE '
            . $this->driver->quoteIdentifier($schema->getTable()) . " ( \n";

        $columnSqls = array();
        foreach( $schema->columns as $name => $column ) {
            if ($column->virtual) {
                continue;
            }
            $columnSqls[] = '  ' . $this->buildColumnSql( $schema, $column );
        }
        $referencesSqls = $this->buildForeignKeys($schema);
        $sql .= join(",\n",array_merge($columnSqls, $referencesSqls));

        $sql .= "\n);\n";
        return $sql;
    }

    public function build(SchemaInterface $schema)
    {
        if ($schema instanceof BaseModel) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
        }
        $sqls = [];
        $tableSqls = $this->buildTable($schema);
        $sqls = array_merge($sqls , $tableSqls);

        $indexSqls = $this->buildIndex($schema);
        $sqls = array_merge($sqls , $indexSqls);
        return $sqls;
    }

    public function buildTable(SchemaInterface $schema)
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

    public function buildIndex(SchemaInterface $schema) 
    {
        // build single column index
        $sqls = array();
        foreach ($schema->columns as $name => $column ) {
            if ($column->index) {
                $table = $schema->getTable() ;
                $indexName = is_string($column->index) ? $column->index 
                    : "idx_" . $table . "_" . $name;
                $query = new CreateIndexQuery($indexName);
                $query->on($table, [$name]);
                if ($column->index_using) {
                    $query->using($column->index_using);
                }
                $sqls[] = $query->toSql($this->driver, new ArgumentArray);
            }
        }
        if ($queries = $schema->getIndexQueries()) {
            foreach ($queries as $query) {
                $sqls[] = $query->toSql($this->driver, new ArgumentArray);
            }
        }
        return $sqls;
    }



    // protected function buildForeignKeySql()



    /**

    It's possible to raise an error like this:

    ERROR 1215 (HY000): Cannot add foreign key constraint

    Cannot find an index in the referenced table where the
    referenced columns appear as the first columns, or column types
    in the table and the referenced table do not match for constraint.
    Note that the internal storage type of ENUM and SET changed in
    tables created with >= InnoDB-4.1.12, and such columns in old tables
    cannot be referenced by such columns in new tables.
    Please refer to http://dev.mysql.com/doc/refman/5.7/en/innodb-foreign-key-constraints.html for correct foreign key definition.
     */
    public function buildForeignKeyConstraint(Relationship $rel)
    {
        $fSchema = new $rel['foreign_schema'];
        $constraint = new Constraint();
        $constraint->foreignKey($rel['self_column']);
        $references = $constraint->references($fSchema->getTable(), (array) $rel['foreign_column']);
        if ($act = $rel->onUpdate) {
            $references->onUpdate($act);
        }
        if ($act = $rel->onDelete) {
            $references->onDelete($act);
        }
        return $constraint;
    }


    public function buildForeignKeys(SchemaInterface $schema)
    {
        $sqls = [];
        foreach ($schema->relations as $rel) {
            switch ($rel['type']) {
            case Relationship::BELONGS_TO:
            case Relationship::HAS_MANY:
            case Relationship::HAS_ONE:
                if ($rel['foreign_schema'] == $rel['self_schema']) {
                    continue;
                }
                if (isset($rel['self_column']) && $rel['self_column'] != 'id' ) 
                {
                    $constraint = $this->buildForeignKeyConstraint($rel);
                    $sqls[] = $constraint->toSql($this->driver, new ArgumentArray);
                }
            }
        }
        return $sqls;
    }
}
