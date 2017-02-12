<?php

namespace Maghead\Sharding\QueryMapper\Gearman;
use GearmanClient;

class GearmanQueryMapper
{
    protected $client;
    
    public function __construct(GearmanClient $client = null)
    {
        $this->client = $client ?: self::createDefaultGearmanClient();
    }

    static protected function createDefaultGearmanClient()
    {
        $client = new GearmanClient;
        $client->addServer();
        return $client;
    }

    protected function map(array $shards, $query)
    {
        $results = [];
        // Send job to each shard.
        foreach ($shards as $shardId => $shard) {
            $result = $this->client->doNormal("reverse", "Hello!");
            // $results[ ];
        }
        return $results;
    }
}
