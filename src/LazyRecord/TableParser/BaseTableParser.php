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
     * @return DeclareSchema
     */
    abstract function getTableSchema($table);

    public function getTableSchemas()
    {
        $tableSchemas = array();

        foreach($this->schemas as $schema) {
            $tableSchema[$schema->getTable()] = $schema;
        }

        // Parse table and try to find the schema
        $tables = $this->getTables();
        foreach ($tables as $table) {
            if (!isset($tableSchemas[$table])) {
                $tableSchemas[$table] = $this->getTableSchema($table);
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



