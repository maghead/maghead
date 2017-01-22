<?php

namespace Maghead;

use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use DateTime;

/**
 * Deflate object value into database.
 */
class Deflator
{
    public static function deflate($value, $isa, BaseDriver $driver = null)
    {
        switch ($isa) {
        case 'int':
            return (int) $value;
        case 'str':
            return (string) $value;
        case 'double':
            return (double) $value;
        case 'float':
            return floatval($value);
        case 'json':
            return json_encode($value);
        case 'bool':
            // Convert string into bool
            if (is_string($value)) {
                if ($value === '' || $value === '0' || strncasecmp($value, 'false', 5) == 0) {
                    $value = false;
                } elseif ($value === '1' ||  strncasecmp($value, 'true', 4) == 0) {
                    $value = true;
                }
            }
            if ($driver) {
                return $driver->deflate($value);
            }

            return (boolean) $value ? 1 : 0;
        }
        if ($value instanceof DateTime) {
            if ($driver instanceof PDOMySQLDriver) {
                return $value->format('Y-m-d H:i:s');
            }

            return $value->format(DateTime::ATOM);
        }

        /* respect the data type to inflate value */
        return $value;
    }
}
