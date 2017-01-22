<?php

namespace Maghead;

use Maghead\Types\DateTime;
use SQLBuilder\Raw;

class Inflator
{
    public static $inflators = array();

    /** 
     * provide a custom inflator for data type.
     */
    public static function register($isa, $inflator)
    {
        self::$inflators[ $isa ] = $inflator;
    }


    public static function inflateInt($value)
    {
        return intval($value);
    }

    public static function inflateBool($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        return boolval($value);
    }

    public static function inflate($value, $isa = null)
    {
        if ($value === null || $isa === null) {
            return $value;
        }

        /*
        if ($value instanceof Raw) {
            return $value->;
        }
         */

        if (isset(self::$inflators[ $isa ])) {
            $inflator = self::$inflators[ $isa ];
            if (is_callable($inflator)) {
                return call_user_func($inflator, $value);
            } elseif (class_exists($inflator, true)) {
                $d = new $inflator();

                return $d->inflate($value);
            }
        }

        switch ($isa) {
        case 'int':
            return intval($value);
        case 'str':
            return (string) $value;
        case 'bool':
            return self::inflateBool($value);
        case 'float':
            return floatval($value);
        case 'json':
            return json_decode($value);
        case 'DateTime':
            // already a DateTime object
            if ($value instanceof DateTime) {
                return $value;
            }
            if (is_string($value)) {
                return new DateTime($value);
            }

            return;
        }

        return $value;
    }
}
