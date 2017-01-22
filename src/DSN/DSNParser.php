<?php

namespace Maghead\DSN;

use Exception;

/**
 * DataSourceName class provides a basic DSN parser.
 */
class DSNParser
{
    public function parse($dsn)
    {
        if (preg_match('/^(\w+):/', $dsn, $matches)) {
            $driver = $matches[1];
        } else {
            throw new Exception("Invalid DSN string: $dsn");
        }
        $reststr = preg_replace('/^\w+:/', '', $dsn);
        $attributes = [];
        $arguments = [];
        $parts = preg_split('/[ ;]/', $reststr);
        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                $arguments[] = $part;
            } else {
                list($key, $val) = explode('=', $part);
                $attributes[ trim($key) ] = trim($val);
            }
        }

        return new DSN($driver, $attributes, $arguments, $dsn);
    }
}
