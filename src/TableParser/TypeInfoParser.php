<?php

namespace Maghead\TableParser;

use Exception;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\PgSQLDriver;

class TypeInfoParser
{
    public static function parseTypeInfo($typeName, BaseDriver $driver = null)
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
            } elseif (isset($matches[1]) && $matches[1] && isset($matches[4]) && $matches[4]) {
                $typeInfo->type = strtolower($matches[1]); // override the original type
                $typeInfo->length = intval($matches[4]);
            } elseif (isset($matches[1]) && $matches[1]) {
                $typeInfo->type = strtolower($matches[1]);
            }

            if (isset($matches[5]) && $matches[5]) {
                $typeInfo->unsigned = true;
            } else {
                $typeInfo->unsigned = false;
            }
        } elseif ($driver instanceof MySQLDriver && preg_match('/(enum|set)\((.*)\)/', $typeName, $matches)) {
            $typeInfo->type = strtolower($matches[1]);
            $values = array();
            $strvalues = explode(',', $matches[2]);
            foreach ($strvalues as $strvalue) {
                // looks like a string
                if (preg_match('/^([\'"])(.*)\\1$/', $strvalue, $matches)) {
                    $values[] = $matches[2];
                } elseif (is_numeric($strvalue)) {
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
        } elseif ($driver instanceof MySQLDriver) {
            switch ($typeInfo->type) {
            case 'tinyint':
                if ($typeInfo->length == 1) {
                    $typeInfo->type = 'boolean';
                    $typeInfo->isa = 'bool';
                    $typeInfo->length = null;
                } elseif (
                    ($typeInfo->unsigned && $typeInfo->length == 3)
                    || (!$typeInfo->unsigned && $typeInfo->length == 4)
                ) {
                    $typeInfo->length = null;
                }
                break;
            case 'integer':
            case 'int':
                $typeInfo->type = 'int';
                if (($typeInfo->unsigned && $typeInfo->length == 10)
                    || (!$typeInfo->unsigned && $typeInfo->length == 11)) {
                    $typeInfo->length = null;
                }
                break;
            case 'smallint':
                if (($typeInfo->unsigned && $typeInfo->length == 5)
                    || (!$typeInfo->unsigned && $typeInfo->length == 6)) {
                    $typeInfo->length = null;
                }
                break;
            case 'mediumint':
                if (($typeInfo->unsigned && $typeInfo->length == 8)
                    || (!$typeInfo->unsigned && $typeInfo->length == 9)) {
                    $typeInfo->length = null;
                }
                break;
            case 'bigint':
                if (($typeInfo->unsigned && $typeInfo->length == 20)
                    || (!$typeInfo->unsigned && $typeInfo->length == 21)) {
                    $typeInfo->length = null;
                }
                break;
            }
        }

        // Update isa property
        if (!$typeInfo->isa) {
            if (in_array($typeInfo->type, ['char', 'varchar', 'text'])) {
                $typeInfo->isa = 'str';
            } elseif (preg_match('/int/', $typeInfo->type)) {
                $typeInfo->isa = 'int';
            } elseif (in_array($typeInfo->type, ['boolean', 'bool'])) {
                $typeInfo->isa = 'bool';
            } elseif (in_array($typeInfo->type, ['blob', 'binary'])) {
                $typeInfo->isa = 'str';
            } elseif ($typeInfo->type == 'double') {
                $typeInfo->isa = 'double';
            } elseif (in_array($typeInfo->type, ['float', 'decimal', 'numeric'])) {
                $typeInfo->isa = 'float';
            } elseif ($typeInfo->type == 'enum') {
                $typeInfo->isa = 'enum';
            } elseif ($typeInfo->type == 'set') {
                $typeInfo->isa = 'set';
            } elseif ($typeInfo->type == 'point') {
                $typeInfo->isa = 'point';
            } elseif ('datetime' === $typeInfo->type || 'date' === $typeInfo->type) {
                $typeInfo->isa = 'DateTime';
            } elseif (preg_match('/timestamp/', $typeInfo->type)) {
                // For postgresql, the 'timestamp' can be 'timestamp with timezone'
                $typeInfo->isa = 'DateTime';
            } elseif ('time' == $typeInfo->type) {
                // DateTime::createFromFormat('H:s','10:00')
                $typeInfo->isa = 'DateTime';
            } else {
                throw new Exception("Unknown type $type");
            }
        }

        return $typeInfo;
    }
}
