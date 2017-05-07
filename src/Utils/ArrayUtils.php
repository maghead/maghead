<?php

namespace Maghead\Utils;

class ArrayUtils
{
    public static function is_assoc_array(&$array)
    {
        $keys = array_keys($array);
        $is = false;
        foreach ($keys as $k) {
            if (!is_numeric($k)) {
                $is = true;
                break;
            }
        }

        return $is;
    }

    public static function is_indexed_array(&$array)
    {
        $keys = array_keys($array);
        $keys2 = array_filter($keys, 'is_numeric');

        return count($keys) == count($keys2);
    }

    public static function describe(array $array)
    {
        $desc = [];
        foreach ($array as $key => $val) {
            if (is_object($val)) {
                $desc[] = "{$key} => " . get_class($val);
            } else {
                $desc[] = "{$key} => {$val}";
            }
        }
        return join(', ',$desc);
    }
}
