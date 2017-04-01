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

    public static function findBin($bin)
    {
        if ($pathstr = getenv('PATH')) {
            $paths = explode(PATH_SEPARATOR, $pathstr);
            if ($binPath = self::lookupBin($bin, $paths)) {
                return $binPath;
            }
        }
        if ($binPath = self::lookupBin($bin, ['/opt/local/bin', '/usr/local/bin', '/usr/bin', '/bin'])) {
            return $binPath;
        }
        return false;
    }

    public static function lookupBin($bin, array $paths)
    {
        foreach ($paths as $path) {
            $p = $path . DIRECTORY_SEPARATOR . $bin;
            if (file_exists($p)) {
                return $p;
            }
        }
        return false;
    }
}
