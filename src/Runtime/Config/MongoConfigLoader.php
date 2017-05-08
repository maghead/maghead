<?php

namespace Maghead\Runtime\Config;

use MongoDB\Client;

class MongoConfigLoader
{
    public static $queryOptions = [
        'typeMap' => [
            'array' => 'array',
            'document' => 'array',
            'root' => 'array',
        ]
    ];

    public static function load(Client $client, $appId)
    {
        $collection = $client->maghead->configs;
        $doc = $collection->findOne(['appId' => $appId], static::$queryOptions);
        if ($doc) {
            return new Config($doc);
        }
    }

    public static function loadFromConfig(Config $config)
    {
        $appId = $config->getAppId();
        if (!$appId) {
            throw new \Exception("config appId entry is required.");
        }

        $configServerUrl = $config->getConfigServerUrl();
        return self::load(new Client($configServerUrl), $appId);
    }


}
