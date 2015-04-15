<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use SQLBuilder\Driver;
use SQLBuilder\Driver\BaseDriver;
use LazyRecord\QueryDriver;

use LazyRecord\Schema\SchemaUtils;
use LazyRecord\Schema\DeclareSchema;

use LazyRecord\ServiceContainer;
use LazyRecord\TableParser\TypeInfo;
use LazyRecord\TableParser\TypeInfoParser;

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

    protected $schemas = array();

    protected $schemaMap = array();

    public function __construct(BaseDriver $driver, PDO $connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;

        $c = ServiceContainer::getInstance();
        $this->config = $c['config_loader'];

        // pre-initialize all schema objects and expand template schema
        $this->schemas = SchemaUtils::findSchemasByConfigLoader($this->config, $c['logger']);
        $this->schemas = SchemaUtils::filterBuildableSchemas($this->schemas);

        foreach($this->schemas as $schema) {
            $this->schemaMap[$schema->getTable()] = $schema;
        }

    }

    public function getDefinedSchemas()
    {
        return $this->schemas;
    }

    /**
     * Implements the query to parse table names from database
     *
     * @return string[] table names
     */
    abstract function getTables();

    /**
     * Implements the logic to reverse table definition to DeclareSchema object.
     *
     *
     * @return DeclareSchema[string tableName] returns (defined table + undefined table)
     */
    abstract function reverseTableSchema($table);

    /**
     * Find all user-defined schema
     */
    public function getTableSchemaMap()
    {
        $tableSchemas = array();
        foreach($this->schemas as $schema) {
            $tableSchemas[$schema->getTable()] = $schema;
        }

        // Parse existing table and try to find the schema
        $tables = $this->getTables();
        foreach ($tables as $table) {
            if (!isset($tableSchemas[$table])) {
                $tableSchemas[$table] = $this->reverseTableSchema($table);
            }
        }
        return $tableSchemas;
    }

    /**
     * Lookup schema by table name
     *
     * @param string $table table name
     * @return DeclareSchema
     */
    public function reverseLookupSchema($table)
    {
        if (isset($this->schemaMap[$table])) {
            return $this->schemaMap[$table];
        }
        return NULL;
    }

    public function typenameToIsa($typeName)
    {
        $typeInfo = TypeInfoParser::parseTypeInfo($typeName);
        return $typeInfo->isa;
    }

}



