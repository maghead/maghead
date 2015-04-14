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

    public function __construct(BaseDriver $driver, PDO $connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;

        $c = ServiceContainer::getInstance();
        $this->config = $c['config_loader'];

        // pre-initialize all schema objects and expand template schema
        $this->schemas = SchemaUtils::findSchemasByConfigLoader($this->config, $c['logger']);
        $this->schemas = SchemaUtils::filterBuildableSchemas($this->schemas);
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
     * @return DeclareSchema[string tableName]
     */
    abstract function getTableSchemaMap($table);

    public function getTableSchemaMaps()
    {
        $tableSchemas = array();

        foreach($this->schemas as $schema) {
            // echo $schema->getTable() . ' => ' . get_class($schema), "\n";
            $tableSchemas[$schema->getTable()] = $schema;
        }

        // Parse existing table and try to find the schema
        $tables = $this->getTables();
        foreach ($tables as $table) {
            if (!isset($tableSchemas[$table])) {
                $tableSchemas[$table] = $this->getTableSchemaMap($table);
            }
        }
        return $tableSchemas;
    }


    public function typenameToIsa($typeName)
    {
        $typeInfo = TypeInfoParser::parseTypeInfo($typeName);
        return $typeInfo->isa;
    }

}



