<?php

namespace Maghead\Sharding;

use Maghead\Manager\ConnectionManager;
use Maghead\Manager\ShardManager;
use Maghead\Sharding\Hasher\Hasher;
use Exception;

class ShardDispatcher
{
    // protected $mapping;

    protected $hasher;

    protected $groups;

    protected $repoClass;

    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager, Hasher $hasher, array $groups, string $repoClass)
    {
        $this->connectionManager = $connectionManager;
        $this->hasher = $hasher;
        // $this->mapping = $mapping;
        $this->groups = $groups;
        $this->repoClass = $repoClass;
        // $this->manager = $manager;
    }

    public function dispatchRead($key)
    {
        $targetGroupId = $this->hasher->hash($key);
        if (!isset($this->groups[$targetGroupId])) {
            throw new Exception("Group $targetGroupId is not defined.");
        }
        $nodes = $this->groups[$targetGroupId]['read'];
        $nodeId = array_rand($nodes); // any node could be used for read
        $read = $this->connectionManager->getConnection($nodeId);
        return new $this->repoClass($read, $read);
    }

    public function dispatchWrite($key)
    {
        $targetGroupId = $this->hasher->hash($key);
        if (!isset($this->groups[$targetGroupId])) {
            throw new Exception("Group $targetGroupId is not defined.");
        }
        $writeNodes = $this->groups[$targetGroupId]['write'];
        $writeNodeId = array_rand($writeNodes); // any node could be used for read
        $write = $this->connectionManager->getConnection($writeNodeId);
        return new $this->repoClass($write, $write);
    }
}
