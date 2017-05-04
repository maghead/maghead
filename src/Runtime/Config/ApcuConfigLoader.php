<?php

namespace Maghead\Runtime\Config;

use ConfigKit\ConfigCompiler;

class ApcuConfigLoader
{

    public static function load($namespace, callable $f1, callable $f2 = null, callable $f3 = null)
    {
        return self::loadWithTtl($namespace, 3600 * 12, $f1, $f2, $f3);
    }


    /**
     * Load config from the YAML config file...
     *
     * @param string $file
     */
    public static function loadWithTtl($namespace, $ttl, callable $f1, callable $f2 = null, callable $f3 = null)
    {
        $cacheKey = "{$namespace}_maghead_config";
        if ($configStash = apcu_fetch($cacheKey)) {
            return new Config($configStash);
        }

        $config = $f1();
        if (!$config && $f2) {
            $config = $f2();
            if (!$config && $f3) {
                $config = $f3();
            }
        }
        apcu_store($cacheKey, $config->stash, $ttl);
        return $config;
    }
}
