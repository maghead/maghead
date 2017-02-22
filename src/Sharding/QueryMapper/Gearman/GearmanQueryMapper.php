<?php

namespace Maghead\Sharding\QueryMapper\Gearman;

use GearmanClient;
use GearmanTask;
use RuntimeException;
use StdClass;

class GearmanQueryMapper
{
    protected $client;
    
    public function __construct(GearmanClient $client = null)
    {
        $this->client = $client ?: self::createDefaultGearmanClient();

        // Setup handler functions
        $this->client->setCreatedCallback([$this, 'handleCreated']);
        $this->client->setStatusCallback([$this, 'handleStatus']);
        $this->client->setCompleteCallback([$this, 'handleComplete']);
        $this->client->setFailCallback([$this, 'handleFail']);
    }

    static protected function createDefaultGearmanClient()
    {
        $client = new GearmanClient;
        $client->addServer();
        return $client;
    }


    public function handleCreated(GearmanTask $task)
    {
        // XXX:
    }

    public function handleStatus(GearmanTask $task)
    {
        // XXX:
    }

    public function handleFail(GearmanTask $task)
    {
        // XXX:
    }

    public function handleComplete(GearmanTask $task, StdClass $context)
    {
        $code = $task->returnCode();
        $context->mapResults[$task->unique()] = [
            "handle" => $task->jobHandle(),
            "data" => unserialize($task->data()),
            "code" => $code,
        ];
    }


    public function map(array $shards, $query)
    {
        $context = new StdClass;
        $context->mapResults = [];

        $tasks = [];
        // Send job to each shard.
        foreach ($shards as $shardId => $shard) {
            $job = new GearmanQueryJob($shardId, $query);
            $tasks[] = $this->client->addTask("query", serialize($job), $context, $shardId);
        }

        if (! $this->client->runTasks()) {
            $err = $this->client->error();
            throw new RuntimeException("ERROR: {$err}");
        }

        $mapResults = [];
        foreach ($context->mapResults as $shardId => $result) {
            foreach ($result['data'] as $nodeId => $data) {
                $mapResults[$nodeId] = $data;
            }
        }
        return $mapResults;
    }
}
