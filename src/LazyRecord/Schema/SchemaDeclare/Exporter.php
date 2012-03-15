<?php
namespace LazyRecord\Schema\SchemaDeclare;

/**
 * solution for var_export
 */
class Exporter 
{
    const space = '  ';

    static function export($data,$level = 0)
    {
        if( is_array($data) ) 
        {
            $level++;
            $str = "array( \n";
            foreach( $data as $k => $v ) {
                if( is_integer($k) ) {
                    $str .= str_repeat( static::space ,$level) . static::export($v,$level + 1) . ",\n";
                }
                else {
                    $str .= str_repeat( static::space ,$level) . "'$k' => " . static::export($v, $level + 1) . ",\n";
                }
            }
            $str .= str_repeat( static::space ,$level > 0 ? $level - 1 : 0) . ")";
            return $str;
        }
        elseif( is_callable($data) && is_object($data) ) {
            return \LazyRecord\ClosureSerializer::serialize($data);
        }
        return var_export($data,true);
    }
}

