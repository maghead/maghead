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

        $typeInfo = new TypeInfo();

        // Type name with precision
        if (preg_match('/^(
             double
            |float
            |decimal
            |numeric
            |int
            |integer
            |tinyint
            |smallint
            |mediumint
            |bigint
            |char
            |varchar
            |character\ varying
            |character
            |binary
            |varbinary
            ) (?: \(  (?:(\d+),(\d+)|(\d+))   \) )?\s*(unsigned)?/ix', $typeName, $matches)) {

            if (isset($matches[1]) && $matches[1] && isset($matches[2]) && isset($matches[3]) && $matches[2] && $matches[3]) {
                $typeInfo->type = strtolower($matches[1]);
                $typeInfo->length = intval($matches[2]);
                $typeInfo->precision = intval($matches[3]);
            } else if (isset($matches[1]) && $matches[1] && isset($matches[4]) && $matches[4]) {
                $typeInfo->type = strtolower($matches[1]); // override the original type
                $typeInfo->length = intval($matches[4]);
            } else if (isset($matches[1]) && $matches[1]) {
                $typeInfo->type = strtolower($matches[1]);
            }

            if (isset($matches[5]) && $matches[5]) {
                $typeInfo->unsigned = TRUE;
            } else {
                $typeInfo->unsigned = FALSE;
            }
        } else if ($driver instanceof MySQLDriver && preg_match('/(enum|set)\((.*)\)/', $typeName, $matches)) {
            $typeInfo->type = strtolower($matches[1]);
            $values = array();
            $strvalues = explode(',',$matches[2]);
            foreach ($strvalues as $strvalue) {
                // looks like a string
                if (preg_match('/^([\'"])(.*)\\1$/', $strvalue, $matches)) {
                    $values[] = $matches[2];
                } else if (is_numeric($strvalue)) {
                    $values[] = intval($strvalue);
                }
            }
            switch ($typeInfo->type) {
            case 'enum':
                $typeInfo->enum = $values;
                break;
            case 'set':
                $typeInfo->set = $values;
                break;
            }
        } else {
            // for type like: 'text' or 'blob'.. type name without length or decimals
            $typeInfo->type = strtolower($typeName);
        }

        // Canonicalization for PgSQL
        if ($driver instanceof PgSQLDriver) {
            if ($typeInfo->type === 'character varying') {
                $typeInfo->type = 'varchar';
            }
        } else if ($driver instanceof MySQLDriver) {


            switch ($typeInfo->type) {
            case 'tinyint':
                if ($typeInfo->length == 1) {
                    $typeInfo->type = 'boolean';
                    $typeInfo->isa = 'bool';
                    $typeInfo->length = NULL;
                } else if (
                    ($typeInfo->unsigned && $typeInfo->length == 3)
                    || (!$typeInfo->unsigned && $typeInfo->length == 4)
                ) {
                    $typeInfo->length = NULL;
                }
                break;
            case 'integer':
            case 'int':
                $typeInfo->type = 'int';
                if (($typeInfo->unsigned && $typeInfo->length == 10)
                    || (!$typeInfo->unsigned && $typeInfo->length == 11))
                {
                    $typeInfo->length = NULL;
                }
                break;
            case 'smallint':
                if (($typeInfo->unsigned && $typeInfo->length == 5)
                    || (!$typeInfo->unsigned && $typeInfo->length == 6)) {
                    $typeInfo->length = NULL;
                }
                break;
            case 'mediumint':
                if (($typeInfo->unsigned && $typeInfo->length == 8)
                    || (!$typeInfo->unsigned && $typeInfo->length == 9))
                {
                    $typeInfo->length = NULL;
                }
                break;
            case 'bigint':
                if (($typeInfo->unsigned && $typeInfo->length == 20)
                    || (!$typeInfo->unsigned && $typeInfo->length == 21)) {
                    $typeInfo->length = NULL;
                }
                break;
            }
        }

        // Update isa property
        if (!$typeInfo->isa) {
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
            } else if (in_array($typeInfo->type, ['float','decimal','numeric'])) {
                $typeInfo->isa = 'float';
            } else if ($typeInfo->type == 'enum') {
                $typeInfo->isa = 'enum';
            } else if ($typeInfo->type == 'set') {
                $typeInfo->isa = 'set';
            } else if ($typeInfo->type == 'point') {
                $typeInfo->isa = 'point';
            } else if ('datetime' === $typeInfo->type || 'date' === $typeInfo->type ) {
                $typeInfo->isa = 'DateTime';
            } else if (preg_match('/timestamp/', $typeInfo->type)) {
                // For postgresql, the 'timestamp' can be 'timestamp with timezone'
                $typeInfo->isa = 'DateTime';
            } else if ('time' == $typeInfo->type) {
                // DateTime::createFromFormat('H:s','10:00')
                $typeInfo->isa = 'DateTime';
            } else {
                throw new Exception("Unknown type $type");
            }
        }
        return $typeInfo;
    }
}


