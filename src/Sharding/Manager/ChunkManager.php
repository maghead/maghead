<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Config;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use LogicException;
use Exception;
use ArrayIterator;
use Iterator;
use IteratorAggregate;

class ChunkManager
{

    protected $config;

    protected $connectionManager;

    protected $shardManager;

    public function __construct(Config $config, ConnectionManager $connectionManager, ShardManager $shardManager = null)
    {
        $this->config = $config;
        $this->connectionManager = $connectionManager;
        $this->shardManager = $shardManager ?: new ShardManager($config, $connectionManager);
    }

    public function removeChunks(ShardMapping $mapping)
    {
        $dbManager = new DatabaseManager($this->connectionManager);
        $chunks = $mapping->getChunks();
        foreach ($chunks as $chunkId => $chunk) {
            $shardId = $chunk['shard'];

            // get shard from the chunk
            $shard = $this->shardManager->getShard($shardId);
            $nodeId = $shard->selectWriteNode();
            $dbManager->drop($nodeId, $chunkId);
        }
    }

    /**
     * Initialize chunks for one shard mapping.
     */
    public function initChunks(ShardMapping $mapping, $numberOfChunks = 4)
    {
        // Get the dbname from master datasource
        $masterDs = $this->connectionManager->getMasterNodeConfig();
        $dsn = DSNParser::parse($masterDs['dsn']);
        $dbname = $dsn->getDatabaseName();

        // Get shards use in this mapping
        $shardIds = $mapping->getShardIds();
        $numberOfChunksPerShard = intdiv($numberOfChunks, count($shardIds));

        $chunkIdList = array_map(function($chunkId) use ($dbname) {
            // special string used by sqlite
            if ($dbname === ":memory:") {
                return "chunk_{$chunkId}";
            }
            return "{$dbname}_{$chunkId}";
        }, range(0, $numberOfChunks));

        $shardChunks = [];

        // the chunks that will override the shard mapping config.
        $chunks = [];
        foreach ($shardIds as $shardId) {
            $shardChunkIds = array_splice($chunkIdList, $numberOfChunksPerShard);
            $shardChunks[ $shardId ] = $shardChunkIds;
            foreach ($shardChunkIds as $chunkId) {
                $chunks[$chunkId] = ['shard' => $shardId];
            }
        }

        $dbManager = new DatabaseManager($this->connectionManager);
        foreach ($shardChunks as $shardId => $chunkIds) {
            foreach ($chunkIds as $chunkId) {
                // get shard from the chunk
                $shard = $this->shardManager->getShard($shardId);
                $writeNodeId = $shard->selectWriteNode();

                // create the db for chunks over the shard
                list($conn, $newds) = $dbManager->create($writeNodeId, $chunkId);

                $chunks[$chunkId]['dsn'] = $newds['dsn'];

                $dbManager->drop($writeNodeId, $chunkId);
            }
        }
        $mapping->setChunks($chunks);

        // TODO: re-scale targets
        return $chunks;
    }
}
