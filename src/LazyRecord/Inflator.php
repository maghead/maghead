<?php
namespace LazyRecord;
use LazyRecord\Types\DateTime;

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
        if( $value === null || $isa === null )
            return $value;

        if( isset(self::$inflators[ $isa ]) ) {
            $inflator = self::$inflators[ $isa ];
            if( is_callable($inflator) ) {
                return call_user_func( $inflator , $value );
            }
            elseif( class_exists($inflator,true) ) {
                $d = new $inflator;
                return $d->inflate( $value );
            }
        }

        /* respect the data type to inflate value */
        if( $isa == 'int' ) {
            return (int) $value;
        }
        elseif( $isa == 'str' ) {
            return (string) $value;
        }
        elseif( $isa == 'bool' ) {
            if( strcasecmp( 'false', $value ) == 0 || $value == '0' ) {
                return false;
            } 
            elseif( strcasecmp( 'true', $value ) == 0 || $value == '1' ) {
                return true;
            }
            return $value ? true : false;
        }
        elseif( $isa == 'float' ) {
            return (float) $value;
        }
        elseif( $isa == 'DateTime' ) {
            // already a DateTime object
            if( is_a( $value , 'DateTime',true) ) {
                return $value;
            }
            if( $value ) {
                return new DateTime( $value );
            }
            return null;
        }
        return $value;
    }

}




