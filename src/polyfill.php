<?php

if (!function_exists('array_keys_join')) {
    function array_keys_join($array, $delim = '-')
    {
        return implode($delim, array_keys($array));
    }
}
