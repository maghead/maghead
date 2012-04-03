<?php
namespace LazyRecord;
use LazyRecord\Types\DateTime;

class Deflator
{
    static $deflators = array();

    /** 
     * provide a custom deflator for data type
     */
    static function register($isa, $deflator)
    {
        self::$deflators[ $isa ] = $deflator;
    }

    static function deflate($value,$isa = null)
    {
        if( $value === null || $isa === null )
            return $value;

        if( isset(self::$deflators[ $isa ]) ) {
            $deflator = self::$deflators[ $isa ];
            if( is_callable($deflator) ) {
                return call_user_func( $deflator , $value );
            }
            elseif( class_exists($deflator,true) ) {
                $d = new $deflator;
                return $d->deflate( $value );
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
            if( is_a( $value , 'DateTime' ) ) {
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




