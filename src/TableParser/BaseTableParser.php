<?php

namespace Maghead\TableParser;

use PDO;
use SQLBuilder\Driver\BaseDriver;
use Maghead\QueryDriver;
use Maghead\Schema\DeclareSchema;
use Maghead\Connection;

abstract class BaseTableParser
{
    /**
     * @var QueryDriver
     */
    protected $driver;

    /**
     * @var Connection
     */
    protected $connection;

    protected $config;

    public function __construct(PDO $connection, BaseDriver $driver)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    /**
     * Implements the query to parse table names from database.
     *
     * @return string[] table names
     */
    abstract public function getTables();

    /**
     * Implements the logic to reverse table definition to DeclareSchema object.
     *
     *
     * @return DeclareSchema[string tableName] returns (defined table + undefined table)
     */
    abstract public function reverseTableSchema($table, $referenceSchema = null);

    /**
     * Find all user-defined schema.
     *
     * This is not used right now.
     */
    public function getTableSchemaMap()
    {
        $tableSchemas = array();

        // Parse existing table and try to find the schema
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $tableSchemas[$table] = $this->reverseTableSchema($table);
        }

        return $tableSchemas;
    }

    public function typenameToIsa($typeName)
    {
        $typeInfo = TypeInfoParser::parseTypeInfo($typeName);

        return $typeInfo->isa;
    }
}
