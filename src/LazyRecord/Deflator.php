<?php
namespace LazyRecord;
use DateTime;
use SQLBuilder\Driver\BaseDriver;

/**
 * Deflate object value into database
 */
class Deflator
{

    static public function deflate($value, $isa, BaseDriver $driver = NULL)
    {
        switch($isa) {

        case 'int':
            return (int) $value;
        case 'str':
            return (string) $value;
        case 'double':
            return (double) $value;
        case 'float':
            return floatval($value);
        case "json":
            return json_encode($value);
        case "DateTime":
            if (is_a($value, 'DateTime',true)) {
                return $value->format( DateTime::ATOM );
            } 
            if (!$value) {
                return NULL;
            }
            return $value; // might return ""
        case 'bool':
            // Convert string into bool
            if (is_string($value)) {
                if ($value === '' || $value === '0' || strncasecmp($value,'false',5) == 0) {
                    $value = false;
                } elseif( $value === '1' ||  strncasecmp($value,'true',4 ) == 0  ) {
                    $value = true;
                }
            }
            if ($driver) {
                return $driver->deflate($value);
            }
            return (boolean) $value ? 1 : 0;
        }
        /* respect the data type to inflate value */
        return $value;
    }

}

