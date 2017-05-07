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

    public static function load($appId, Client $client)
    {
        $collection = $client->maghead->configs;
        $doc = $collection->findOne(['appId' => $appId], static::$queryOptions);
        if ($doc) {
            return new Config($doc['stash']);
        }
    }
}
