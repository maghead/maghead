<?php

namespace Maghead;

use Maghead\Schema\SchemaFinder;
use Maghead\Schema\SchemaLoader;

class Utils
{
    public static function evaluate($data, $params = array())
    {
        if ($data && is_callable($data)) {
            return call_user_func_array($data, $params);
        }

        return $data;
    }
}
