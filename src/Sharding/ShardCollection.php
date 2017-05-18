<?php

namespace Maghead\Sharding;

use LogicException;
use Maghead\Sharding\Hasher\FastHasher;
use Maghead\Sharding\Hasher\Hasher;
use Ramsey\Uuid\Uuid;
use Magsql\Universal\Query\UUIDQuery;
use ArrayObject;

class ShardCollection extends ArrayObject
{
    protected $mapping;

    protected $repoClass;

    private $dispatcher;

    public function __construct(array $shards, ShardMapping $mapping = null, $repoClass = null)
    {
        parent::__construct($shards);
        $this->mapping = $mapping;
        $this->repoClass = $repoClass;
    }

    /**
     * A simple UUID generator base on Ramsey's implementation.
     *
     * The reason that we define this method here is that:
     *
     * Magsql\Universal\UUIDQuery needs the db connection to get the UUID
     * generated by database.
     */
    public function generateUUID()
    {
        // See https://github.com/ramsey/uuid/wiki/Ramsey%5CUuid-Cookbook
        if ($keyGenerator = $this->mapping->getKeyGenerator()) {
            switch ($keyGenerator) {
                case "uuid-v1":
                    return Uuid::uuid1();
                case "uuid-v4":
                    return Uuid::uuid4();
                    /*
                case "uuid-v3":
                    return Uuid::uuid3(Uuid::NAMESPACE_DNS, 'php.net');
                case "uuid-v5":
                    return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');
                     */
            }
        }
        return Uuid::uuid4();
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function dispatch($key)
    {
        if (!$this->dispatcher) {
            $this->dispatcher = $this->createDispatcher();
        }
        return $this->dispatcher->dispatch($key);
    }

    public function createDispatcher()
    {
        return new ShardDispatcher($this->mapping, $this);
    }

    public function __call($method, $args)
    {
        if (!$this->repoClass) {
            throw new LogicException("To support shard map, you need to pass Repo class.");
        }
        $results = [];
        foreach ($this as $shardId => $shard) {
            $repo = $shard->createRepo($this->repoClass);
            $results[$shardId] = call_user_func_array([$repo, $method], $args);
        }
        return $results;
    }

    public function first(callable $callback)
    {
        if (!$this->repoClass) {
            throw new LogicException("To support shard map, you need to pass Repo class.");
        }
        foreach ($this as $shardId => $shard) {
            $repo = $shard->createRepo($this->repoClass);
            if ($ret = $callback($repo, $shard)) {
                return $ret;
            }
        }
        return null;
    }

    /**
     * locateBy method locates the shard by the given callback.
     *
     * the shard will be returned if the callback return true
     *
     * @return Maghead\Sharding\Shard
     */
    public function locateBy(callable $callback)
    {
        if (!$this->repoClass) {
            throw new LogicException("To support shard map, you need to pass Repo class.");
        }
        foreach ($this as $shardId => $shard) {
            $repo = $shard->createRepo($this->repoClass);
            if ($callback($repo, $shard)) {
                return $shard;
            }
        }
        return null;
    }


    /**
     * Map an operation over the repository on each shard.
     *
     * This method runs the operation in sync mode.
     *
     * shardsMap returns the result of each shard. the returned value can be
     * anything.
     *
     * @return array mapResults
     */
    public function map(callable $callback)
    {
        if (!$this->repoClass) {
            throw new LogicException("To support shard map, you need to pass Repo class.");
        }
        $mapResults = [];
        foreach ($this as $shardId => $shard) {
            $repo = $shard->createRepo($this->repoClass);
            $mapResults[$shardId] = $callback($repo, $shard);
        }
        return $mapResults;
    }

    /**
     * Route a function call to a shard by using the given shard key.
     *
     * Locate a shard by the sharding key, and execute the callback.
     *
     * @return mixed result.
     */
    public function locateAndExecute($shardKey, callable $callback)
    {
        if (!$this->repoClass) {
            throw new LogicException("To support shard map, you need to pass Repo class.");
        }
        $dispatcher = $this->createDispatcher();
        $shard = $dispatcher->dispatch($shardKey);
        $repo = $shard->createRepo($this->repoClass);
        return $callback($repo, $shard);
    }
}
