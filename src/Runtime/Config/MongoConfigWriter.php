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

    public static function write(Client $client, Config $config)
    {
        $appId = $config->getAppId();
        if (!$appId) {
            throw new \Exception("config appId entry is required.");
        }
        $collection = $client->maghead->configs;
        $result = $collection->updateOne(
            [ 'appId' => $appId ],
            [ '$set' => $config->stash ],
            [ 'upsert' => true ]);
        return $result;
    }

    public static function removeById(Client $client, $appId)
    {
        $collection = $client->maghead->configs;
        return $collection->deleteOne([ 'appId' => $appId ]);
    }

    public static function remove(Config $config)
    {
        $configServerUrl = $config->getConfigServerUrl();
        $appId = $config->getAppId();

        if (!$appId) {
            throw new \Exception("config appId entry is required.");
        }

        $client = new Client($configServerUrl);

        $collection = $client->maghead->configs;
        return $collection->deleteOne([ 'appId' => $appId ]);
    }
}
