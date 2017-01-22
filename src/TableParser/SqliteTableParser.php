<?php

namespace Maghead\TableParser;

use PDO;
use Exception;
use LogicException;
use Maghead\Schema\DeclareSchema;
use SQLBuilder\Raw;

class SqliteTableParser extends BaseTableParser
{
    public function getTables()
    {
        $stm = $this->connection->query("SELECT * FROM sqlite_master WHERE type='table';");
        $rows = $stm->fetchAll(PDO::FETCH_OBJ);
        $tables = array();
        foreach ($rows as $row) {
            if ($row->tbl_name == 'sqlite_sequence') {
                continue;
            }
            $tables[] = $row->tbl_name;
        }

        return $tables;
    }

    public function getTableSql($table)
    {
        $stm = $this->connection->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '$table'");
        // $stm = $this->connection->query("PRAGMA table_info($table)");
        if ($row = $stm->fetch(PDO::FETCH_OBJ)) {
            return $row->sql;
        }
    }

    public function parseTableSql($table)
    {
        $sql = $this->getTableSql($table);
        if ($sql && preg_match('#`?(\w+)`?\s*\((.*)\)#ism', $sql, $regs)) {
            $columns = array();
            $name = $regs[1];
            $columnstr = $regs[2];
            $parser = new SqliteTableDefinitionParser($columnstr);
            $tableDef = $parser->parseColumnDefinitions();

            return $tableDef;
        }
    }

    public function reverseTableSchema($table, $referenceSchema = null)
    {
        $tableDef = $this->parseTableSql($table);
        $schema = new DeclareSchema();
        $schema->columnNames = $schema->columns = array();
        $schema->table($table);

        foreach ($tableDef->columns as $columnDef) {
            $name = $columnDef->name;
            $column = $schema->column($name);

            if (!isset($columnDef->type)) {
                throw new LogicException("Missing column type definition on $table.$name.");
            }

            $type = $columnDef->type;
            $typeInfo = TypeInfoParser::parseTypeInfo($type, $this->driver);

            // if the reference schema is given, and the type is similar 
            // we should just apply the type from schema.
            // if ($referenceSchema) { }

            // Cast INTEGER to INT
            if (strtoupper($type) == 'INTEGER') {
                $type = 'INT';
            }
            $column->type($type);

            if (isset($columnDef->length)) {
                $column->length($columnDef->length);
            }
            if (isset($columnDef->decimals)) {
                $column->decimals($columnDef->decimals);
            }

            $isa = $this->typenameToIsa($type);
            $column->isa($isa);

            if (isset($columnDef->notNull) && $columnDef->notNull !== null) {
                if ($columnDef->notNull) {
                    $column->notNull();
                } else {
                    $column->null();
                }
            }

            if (isset($columnDef->primary)) {
                $column->primary(true);
                $schema->primaryKey = $name;

                if (isset($columnDef->autoIncrement)) {
                    $column->autoIncrement(true);
                }
            } elseif (isset($columnDef->unique)) {
                $column->unique(true);
            }

            if (isset($columnDef->default)) {
                $default = $columnDef->default;
                if (is_scalar($default)) {
                    $column->default($default);
                } elseif ($default instanceof Token && $default->type == 'literal') {
                    $column->default(new Raw($default->val));
                } else {
                    throw new Exception('Incorrect literal token');
                }
            }
        }

        return $schema;
    }
}
