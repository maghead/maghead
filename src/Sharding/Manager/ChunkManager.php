<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Config;
use Maghead\Schema\SchemaUtils;

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

    protected $dataSourceManager;

    protected $shardManager;

    /**
     * @var integer The default hash range 4294967296 = 2 ** 32
     */
    const HASH_RANGE = 4294967296;

    public function __construct(Config $config, DataSourceManager $dataSourceManager, ShardManager $shardManager = null)
    {
        $this->config = $config;
        $this->dataSourceManager = $dataSourceManager;
        $this->shardManager = $shardManager ?: new ShardManager($config, $dataSourceManager);

        /*
        $this->connectionManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
        */
    }

    /**
     * Compute the distribution by the given shard mapping
     *
     * @return array distribution info
     */
    public function computeDistribution(ShardMapping $mapping, $numberOfChunks = 32, $range = 4294967296)
    {
        $shardIds = $mapping->getShardIds();
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil($range / $numberOfChunks));
        return [
            "hashRange" => self::HASH_RANGE,
            "numberOfChunks" => $numberOfChunks,
            "rangePerChunk"  => $rangePerChunk,
            "chunksPerShard" => $chunksPerShard,
            "shards"         => $shardIds,
        ];
    }

    /**
     * Distribute the chunks.
     *
     */
    public function distribute(ShardMapping $mapping, $numberOfChunks = 32)
    {
        $shardIds = $mapping->getShardIds();
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil(self::HASH_RANGE / $numberOfChunks));

        $chunks = [];
        $r = 0;
        foreach ($shardIds as $shardId) {
            for ($i = 0 ; $i < $chunksPerShard; $i++) {
                $r += $rangePerChunk;
                if ($r + $rangePerChunk > self::HASH_RANGE) {
                    $r = self::HASH_RANGE;
                }
                $chunks[$r] = [ 'shard' => $shardId ];
                if ($r + $rangePerChunk > self::HASH_RANGE) {
                    break;
                }
            }
        }
        $mapping->setChunks($chunks);
        return $chunks;
    }

    /**
     * Move a chunk
     */
    public function move(ShardMapping $mapping, $chunkIndex, $targetShard)
    {
        $chunk = $mapping->loadChunk($chunkIndex);
        $shardId = $chunk->getShardId();
        if ($targetShard === $shardId) {
            throw new InvalidArgumentException("$targetShard == $shardId");
        }
        // var_dump($chunk);

        $schemas = SchemaUtils::findSchemasByConfig($this->config);
        $schemas = SchemaUtils::filterShardMappingSchemas($mapping->id, $schemas);

        $shardKey = $mapping->getKey();
        $shards = $this->shardManager->loadShardCollectionOf($mapping->id);
        $shardDispatcher = new ShardDispatcher($mapping, $shards);

        // get shard Id of the chunk
        $srcConn = $this->dataSourceManager->getWriteConnection($shardId);
        $dstConn = $this->dataSourceManager->getWriteConnection($targetShard);

        $moved = [];
        foreach ($schemas as $schema) {
            if ($schema->globalTable) {
                continue;
            }

            $srcRepo = $schema->newRepo($srcConn);
            $dstRepo = $schema->newRepo($dstConn);

            // In the chunk
            $keys = array_filter($srcRepo->fetchDistinctShardKeys(), function($k) use ($shardDispatcher, $chunk) {
                $index = $shardDispatcher->hash($k);

                // echo "key($k) -> index($index): {$chunk->from} < {$index} && {$index} <= {$chunk->index} \n";

                return $chunk->from < $index && $index <= $chunk->index;
            });

            if (!empty($keys)) {
                $select = $srcRepo->select();
                $select->where()->in($shardKey, $keys);
                $records = $select->fetch();
                foreach ($records as $record) {
                    $moved[] = $record->move($dstRepo);
                }
            }
        }
        return $moved;
    }


    public function split(ShardMapping $mapping, $chunkIndex, $targetShard)
    {
        // TODO: implement chunk split
    }
}
