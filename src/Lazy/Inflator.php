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
        elseif( $dataType == 'bool' ) {
            return (boolean) $value;
        }
        elseif( $dataType == 'float' ) {
            return (float) $value;
        }
        return $value;
    }

}

