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
    public static function load($appId, $file, $ttl = 0)
    {
        // use the modification time as the cache key, and so if the file is modified,
        // we will reload the file.
        if (is_link($file)) {
            $file = realpath($file);
        }

        if ($ttl === false) {
            return self::loadRemoteConfigIfFound($appId, $file);
        }

        $mtime = filemtime($file);
        return ApcuConfigLoader::loadWithTtl("{$appId}_{$mtime}", $ttl, function() use($appId, $file) {
            return self::loadRemoteConfigIfFound($appId, $file);
        });
    }

    private static function loadRemoteConfigIfFound($appId, $file)
    {
        $config = FileConfigLoader::load($file);
        if ($configServerUrl = $config->getConfigServerUrl()) {
            // Use the config from server if any
            if ($serverConfig = MongoConfigLoader::load($appId, new Client($configServerUrl))) {
                return $serverConfig;
            }
        }
        return $config;
    }


}
