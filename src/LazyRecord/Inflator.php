<?php
namespace LazyRecord;
use LazyRecord\Types\DateTime;
use SQLBuilder\Raw;

class Inflator
{
    static $inflators = array();

    /** 
     * provide a custom inflator for data type
     */
    static function register($isa, $inflator)
    {
        self::$inflators[ $isa ] = $inflator;
    }

    static function inflate($value,$isa = null)
    {
        if ($value === null || $isa === null) {
            return $value;
        }

        /*
        if ($value instanceof Raw) {
            return $value->;
        }
         */

        if( isset(self::$inflators[ $isa ]) ) {
            $inflator = self::$inflators[ $isa ];
            if (is_callable($inflator) ) {
                return call_user_func( $inflator , $value );
            }
            elseif( class_exists($inflator,true) ) {
                $d = new $inflator;
                return $d->inflate( $value );
            }
        }

        switch($isa) {
        case "int":
            return (int) $value;
        case "str":
            return (string) $value;
        case "bool":
            if (is_string($value)) {
                if (strcasecmp('false', $value) == 0 || $value == '0') {
                    return false;
                } elseif(strcasecmp('true', $value) == 0 || $value == '1' ) {
                    return true;
                }
            }
            return $value ? true : false;
        case "float":
            return floatval($value);
        case "json":
            return json_decode($value);
        case "DateTime":
            // already a DateTime object
            if( is_a( $value , 'DateTime',true) ) {
                return $value;
            }
            if ( $value && date_parse($value) !== false ) {
                return new DateTime( $value );
            }
            return null;
        }
        return $value;
    }

}




