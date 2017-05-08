<?php

namespace Maghead\Runtime\Config;

use MongoDB\Client;

/**
 * AutoConfigLoader loads the config from file, and then
 * check if the configServer attribute is defined,
 * if the configServer url is found, then update the config object from the
 * config server.
 */
class AutoConfigLoader
{
    /**
     * @param string $appId applicationId, used as the namespace for cache.
     * @param string $file the path of the config file.
     * @param numeric $ttl the default time to live seconds for apcu cache.
     */
    public static function load($file, $ttl = 0, $offline = false)
    {
        // use the modification time as the cache key, and so if the file is modified,
        // we will reload the file.
        if (is_link($file)) {
            $file = realpath($file);
        }

        if ($offline) {
            return FileConfigLoader::load($file, true);
        }

        if ($ttl === false) {
            return self::loadRemoteConfigIfFound($file, true);
        }

        $mtime = filemtime($file);
        // TODO: fix for the app ID here....
        return ApcuConfigLoader::loadWithTtl("config_{$mtime}", $ttl, function() use($file) {
            // Since we have cache, we can force reload from the file.
            return self::loadRemoteConfigIfFound($file, true);
        });
    }

    private static function loadRemoteConfigIfFound($file, $force = false)
    {
        $config = FileConfigLoader::load($file, $force);
        if ($config->getAppId()) {
            if ($serverConfig = MongoConfigLoader::loadFromConfig($config)) {
                return $serverConfig;
            }
        }
        return $config;
    }
}
