<?php

namespace Maghead\Runtime\Config;

use MongoDB\Client;

class MongoConfigLoader
{
    public static function load($appId, Client $client)
    {
        $collection = $client->maghead->configs;
        $doc = $collection->findOne(['appId' => $appId], [
            'typeMap' => [
                'array' => 'array',
                'document' => 'array',
                'root' => 'array',
            ],
        ]);
        if ($doc) {
            return new Config($doc['stash']);
        }
    }
}
