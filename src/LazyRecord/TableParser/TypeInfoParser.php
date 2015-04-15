<?php
namespace LazyRecord\TableParser;
use Exception;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SqliteDriver;

class TypeInfoParser
{
    static public function parseTypeInfo($typeName, BaseDriver $driver = NULL)
    {
        $type = strtolower($typeName);

        $typeInfo = new TypeInfo($type);

        // Type name with precision
        if (preg_match('/^(double
            |float
            |int
            |tinyint
            |smallint
            |mediumint
            |bigint
            |char
            |varchar
            |character\ varying
            |character
            ) (?: \(  (?:(\d+),(\d+)|(\d+))   \) )?/x', $type, $matches)) {

            if (isset($matches[1]) && $matches[1] && isset($matches[2]) && isset($matches[3]) && $matches[2] && $matches[3]) {
                $typeInfo->type = $matches[1];
                $typeInfo->length = intval($matches[2]);
                $typeInfo->precision = intval($matches[3]);
            } else if (isset($matches[1]) && $matches[1] && isset($matches[4]) && $matches[4]) {
                $typeInfo->type = $matches[1]; // override the original type
                $typeInfo->length = intval($matches[4]);
            } else if (isset($matches[1]) && $matches[1]) {
                $typeInfo->type = $matches[1];
            }
        }

        // Canonicalization for PgSQL
        if ($driver instanceof PgSQLDriver) {
            if ($typeInfo->type === 'character varying') {
                $typeInfo->type = 'varchar';
            }
        } else if ($driver instanceof MySQLDriver) {

            if ($typeInfo->type === 'tinyint' && $typeInfo->length == 1) {
                $typeInfo->type = 'boolean';
                $typeInfo->isa = 'bool';
                $typeInfo->length = NULL; // reset NULL
            } else if (($typeInfo->type === 'integer' || $typeInfo->type === 'int') && $typeInfo->length == 11) {
                $typeInfo->type = 'int';
                $typeInfo->length = NULL; // reset NULL
            } else if ($typeInfo->type === 'mediumint' && $typeInfo->length == 8) {
                $typeInfo->length = NULL; // reset NULL
            } else if ($typeInfo->type === 'smallint' && $typeInfo->length == 5) {
                $typeInfo->length = NULL; // reset NULL
            } else if ($typeInfo->type === 'bigint' && $typeInfo->length == 20) {
                $typeInfo->length = NULL; // reset NULL
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
        } else if ($typeInfo->type == 'point') {
            $typeInfo->isa = 'point';
        } else if ('datetime' === $typeInfo->type || 'date' === $typeInfo->type ) {
            $typeInfo->isa = 'DateTime';
        } 
        // For postgresql, the 'timestamp' can be 'timestamp with timezone'
        else if (preg_match('/timestamp/', $typeInfo->type)) 
        {
            $typeInfo->isa = 'DateTime';
        } 
        else if ('time' == $typeInfo->type) 
        {
            // DateTime::createFromFormat('H:s','10:00')
            $typeInfo->isa = 'DateTime';
        } else {
            throw new Exception("Unknown type $type");
        }
        return $typeInfo;
    }
}


