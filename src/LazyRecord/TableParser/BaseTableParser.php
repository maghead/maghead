<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use SQLBuilder\Driver;
use SQLBuilder\Driver\BaseDriver;
use LazyRecord\QueryDriver;
use LazyRecord\TableParser\TypeInfo;

abstract class BaseTableParser
{
    public $driver;
    public $connection;

    public function __construct(BaseDriver $driver, PDO $connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    abstract function getTables();
    abstract function getTableSchema($table);

    public function getTableSchemas()
    {
        $tableSchemas = array();
        $tables = $this->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $this->getTableSchema( $table );
        }
        return $tableSchemas;
    }

    public function parseTypeInfo($typeName)
    {
        $type = strtolower($typeName);

        $typeInfo = new TypeInfo($type);

        // Type name with precision
        if (preg_match('/^(double|float|int|tinyint|smallint|mediumint|bigint|varchar) (?: \(  (?:(\d+),(\d+)|(\d+))   \) )?/x', $type, $matches)) {
            if (isset($matches[1]) && isset($matches[2]) && isset($matches[3])) {
                $typeInfo->type = $matches[1];
                $typeInfo->length = intval($matches[2]);
                $typeInfo->precision = intval($matches[3]);
            } else if (isset($matches[1]) && isset($matches[4])) {
                $typeInfo->type = $matches[1]; // override the original type
                $typeInfo->length = intval($matches[4]);
            } else if (isset($matches[1])) {
                $typeInfo->type = $matches[1];
            }
        }

        if (in_array($typeInfo->type,[ 'char', 'varchar', 'text' ])) {
            $typeInfo->isa = 'str';
        } else if (preg_match('/int/', $typeInfo->type)) {
            $typeInfo->isa = 'int';
        } else if (in_array($typeInfo->type, ['boolean', 'bool'])) {
            $typeInfo->isa = 'bool';
        } else if (in_array($typeInfo->type, ['blob', 'binary'])) {
            $typeInfo->isa = 'str';
        } else if ($typeInfo->type == 'double') {
            $typeInfo->isa = 'double';
        } else if ($typeInfo->type == 'float') {
            $typeInfo->isa = 'float';
        } else if ($typeInfo->type == 'tinyint' && $typeInfo->length == 1) {
            $typeInfo->isa = 'bool';
            $typeInfo->type = 'bool';
        } else if ($typeInfo->type == 'point') {
            $typeInfo->isa = 'point';
        } else if ('datetime' === $typeInfo->type || 'date' === $typeInfo->type ) {
            $typeInfo->isa = 'DateTime';
        } else if (preg_match('/timestamp/', $typeInfo->type)) {
            $typeInfo->isa = 'DateTime';
        } else if ('time' == $typeInfo->type) {
            // DateTime::createFromFormat('H:s','10:00')
            $typeInfo->isa = 'DateTime';
        } else {
            throw new Exception("Unknown type $type");
        }
        return $typeInfo;
    }

    public function typenameToIsa($typeName)
    {
        $typeInfo = $this->parseTypeInfo($typeName);
        return $typeInfo->isa;
    }

}



