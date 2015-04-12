<?php
namespace LazyRecord\SqlBuilder;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateIndexQuery;
use SQLBuilder\Universal\Syntax\Constraint;

use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\TemplateSchema;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;
use LazyRecord\BaseModel;
use LazyRecord\Schema\ColumnDeclare;

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

    abstract public function buildColumnSql(SchemaInterface $schema, ColumnDeclare $column);

    public function createTable(SchemaInterface $schema)
    {
        $sql = 'CREATE TABLE ' 
            . $this->driver->quoteIdentifier($schema->getTable()) . " ( \n";
        $columnSql = array();
        foreach( $schema->columns as $name => $column ) {
            if ($column->virtual) {
                continue;
            }
            $columnSql[] = '  ' . $this->buildColumnSql( $schema, $column );
        }
        $sql .= join(",\n",$columnSql);
        $sql .= "\n);\n";
        return $sql;
    }

    public function build(SchemaInterface $schema)
    {
        if ($schema instanceof BaseModel) {
            $model = $schema;
            $schema = new DynamicSchemaDeclare($model);
        }


        if ($schema instanceof TemplateSchema) {
            $sqls = [];
            $extraSchemas = $schema->yieldSchemas();
            foreach ($extraSchemas as $es) {
                $esTableSqls = $this->buildTable($es);
                $sqls =  array_merge($sqls , $esTableSqls);

                $esIndexSqls = $this->buildIndex($es);
                $sqls =  array_merge($sqls , $esIndexSqls);
            }
            return $sqls;
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
        // Single column index
        $sqls = array();
        foreach ($schema->columns as $name => $column ) {
            if ($column->index) {
                $indexName = is_string($column->index) ? $column->index 
                    : "idx_" . $schema->getTable() . "_" . $name;

                $query = new CreateIndexQuery($indexName);
                $query->on($schema->getTable(), (array) $name);
                $sqls[] = $query->toSql($this->driver, new ArgumentArray);
            }
        }
        return $sqls;
    }


    public function buildForeignKeys(SchemaInterface $schema)
    {
        return array(); // FIXME

        $sqls = array();
        if ($this->driver->type == 'sqlite') {
            return $sqls;
        }

        foreach ($schema->relations as $rel) {
            switch ( $rel['type'] ) {
            case Relationship::BELONGS_TO:
            case Relationship::HAS_MANY:
            case Relationship::HAS_ONE:
                if (isset($rel['self_column']) && $rel['self_column'] != 'id' ) 
                {
                    $n = $rel['self_column'];
                    $column = $schema->getColumn($n);
                    if ( $column->isa == "str" ) {
                        continue;
                    }

                    
                    $fSchema = new $rel['foreign_schema'];

                    $constraint = new Constraint();
                    $constraint->foreignKey($rel['self_column']);
                    $constraint->reference($fSchema->getTable(), (array) $rel['foreign_column']);
                    // $constraint->onUpdate('CASCADE');
                    // $constraint->onDelete('CASCADE');
                    $sqls[] = $query->toSql($this->driver, new ArgumentArray);
                }
            }
        }
        return $sqls;
    }





}




