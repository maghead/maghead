<?php

namespace Maghead\Sharding\QueryMapper\Gearman;

use GearmanClient;
use GearmanTask;
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


    public function handleCreated(GearmanTask $task) {

    }

    public function handleStatus(GearmanTask $task)
    {

    }

    public function handleComplete(GearmanTask $task, StdClass $context)
    {
        $code = $task->returnCode();
        $context->results[$task->unique()] = [
            "handle" => $task->jobHandle(),
            "data" => unserialize($task->data()),
            "code" => $code,
        ];
    }

    public function handleFail(GearmanTask $task)
    {

    }

    public function map(array $shards, $query)
    {

        $context = new StdClass;
        $context->results = [];

        $tasks = [];
        // Send job to each shard.
        foreach ($shards as $shardId => $shard) {
            $job = new GearmanQueryJob($shardId, $query);
            $tasks[] = $this->client->addTask("query", serialize($job), $context, $shardId);
        }

        if (! $this->client->runTasks()) {
            echo "ERROR " . $this->client->error() . "\n";
            exit;
        }
        // var_dump($context);

        $results = [];
        foreach ($context->results as $shardId => $result) {
            $results = array_merge($results, $result['data']);
            // $results[$shardId]
            // var_dump($result);
        }
        return $results;
    }
}
