<?php
namespace LazyRecord\SchemaDeclare;



/**
 * solution for var_export
 */
class Exporter 
{


    static function export($data)
    {
        if( is_array($data) ) 
        {
            $str = "array( \n";
            foreach( $data as $k => $v ) {
                if( is_integer($k) ) {
                    $str .= static::export($v) . ",\n";
                }
                else {
                    $str .= "'$k' => " . static::export($v) . ",\n";
                }
            }
            $str .= ") ";
            return $str;
        }
        elseif( is_callable($data) && is_object($data) ) {
            return \LazyRecord\ClosureSerializer::serialize($data);
        }
        return var_export($data,true);
    }


}



