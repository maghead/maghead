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

    public static function write($appId, Client $client, Config $config)
    {
        $collection = $client->maghead->configs;
        $result = $collection->updateOne(
            [ 'appId' => $appId ],
            [ '$set' => ['appId' => $appId , 'stash' => $config->stash ]],
            [ 'upsert' => true ]);
        return $result;
    }

    public static function remove($appId, Client $client)
    {
        $collection = $client->maghead->configs;
        return $collection->deleteOne([ 'appId' => $appId ]);
    }
}
