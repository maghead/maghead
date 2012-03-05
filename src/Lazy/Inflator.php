<?php
namespace Lazy;

/**
 * inflate value into database
 */
class Inflator
{

    static function inflate($value, $dataType)
    {
        /* respect the data type to inflate value */
        if( $dataType == 'int' ) {
            return (int) $value;
        }
        elseif( $dataType == 'str' ) {
            return (string) $value;
        }
        elseif( $dataType == 'DateTime' ) {
            if( is_a($value, 'DateTime') ) {
                return $value->format( DateTime::ATOM );
            }
            return $value; // might return ""
        }
        elseif( $dataType == 'bool' ) {
            /**
             * PDO can't accept false or true boolean value, can only accept string
             * https://bugs.php.net/bug.php?id=33876
             *
             * should cast to string for now.
             */
            if( is_string($value) ) {
                if( ! $value ) {
                    return $value = 'FALSE';
                }
                elseif( $value === '1' ) {
                    return $value = 'TRUE';
                }
                elseif( $value === '0' ) {
                    return $value = 'FALSE';
                }
                elseif( strncasecmp($value,'false',5) == 0 ) {
                    return $value = 'FALSE';
                } 
                elseif( strncasecmp($value,'true',4 ) == 0 ) {
                    return $value = 'TRUE';
                }
            }
            elseif( is_null($value) ) {
                return 'NULL';
            }

            $value = (boolean) $value;
            if( $value ) {
                return $value = 'TRUE';
            } else {
                return $value = 'FALSE';
            }
            return $value;
        }
        elseif( $dataType == 'float' ) {
            return (float) $value;
        }
        return $value;
    }

}

