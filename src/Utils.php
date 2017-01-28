<?php

namespace Maghead;

use Maghead\Schema\SchemaFinder;
use Maghead\Schema\SchemaLoader;

class Utils
{
    public static function breakDSN($dsn)
    {
        // break DSN string down into parameters
        $params = array();
        if (strpos($dsn, ':') === false) {
            $params['driver'] = $dsn;

            return $params;
        }

        list($driver, $paramString) = explode(':', $dsn, 2);
        $params['driver'] = $driver;

        if ($paramString === ':memory:') {
            $params[':memory:'] = 1;

            return $params;
        }

        $paramPairs = explode(';', $paramString);
        foreach ($paramPairs as $pair) {
            if (preg_match('#(\S+)=(\S+)#', $pair, $regs)) {
                $params[$regs[1]] = $regs[2];
            }
        }

        return $params;
    }

    public static function evaluate($data, $params = array())
    {
        if ($data && is_callable($data)) {
            return call_user_func_array($data, $params);
        }

        return $data;
    }
}
