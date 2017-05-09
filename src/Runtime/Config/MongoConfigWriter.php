<?php

namespace Maghead\Runtime\Config;

use MongoDB\Client;

class MongoConfigWriter
{

    public static $queryOptions = [
        'typeMap' => [
            'array' => 'array',
            'document' => 'array',
            'root' => 'array',
        ]
    ];

    public static function createClient(Config $config)
    {
        $configServerUrl = $config->getConfigServerUrl();
        return new Client($configServerUrl);
    }

    public static function write(Config $config, Client $client = null)
    {
        $appId = $config->getAppId();
        if (!$appId) {
            throw new \Exception("config appId entry is required.");
        }

        if (!$client) {
            $client = self::createClient($config);
        }

        $collection = $client->maghead->configs;
        $result = $collection->updateOne(
            [ 'appId' => $appId ],
            [ '$set' => $config->stash ],
            [ 'upsert' => true ]);
        return $result;
    }

    public static function removeById($appId, Client $client)
    {
        $collection = $client->maghead->configs;
        return $collection->deleteOne([ 'appId' => $appId ]);
    }

    public static function remove(Config $config, Client $client = null)
    {
        $appId = $config->getAppId();
        if (!$appId) {
            throw new \Exception("config appId entry is required.");
        }
        if (!$client) {
            $client = self::createClient($config);
        }

        $collection = $client->maghead->configs;
        return $collection->deleteOne([ 'appId' => $appId ]);
    }

}
