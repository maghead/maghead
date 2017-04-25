<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\Chunk;
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
    protected $mapping;

    public function __construct(ShardMapping $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * Compute the distribution by the given shard mapping
     *
     * @return array distribution info
     */
    public function computeDistribution($numberOfChunks = 32, $range = 4294967296)
    {
        $shardIds = $this->mapping->getShardIds();
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil($range / $numberOfChunks));
        return [
            "hashRange" => Chunk::HASH_RANGE,
            "numberOfChunks" => $numberOfChunks,
            "rangePerChunk"  => $rangePerChunk,
            "chunksPerShard" => $chunksPerShard,
            "shards"         => $shardIds,
        ];
    }

    /**
     * Distribute the chunks.
     */
    public function distribute($numberOfChunks = 32)
    {
        $shardIds = $this->mapping->getShardIds();
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil(Chunk::HASH_RANGE / $numberOfChunks));

        $chunks = [];
        $r = 0;
        foreach ($shardIds as $shardId) {
            for ($i = 0 ; $i < $chunksPerShard; $i++) {
                $r += $rangePerChunk;
                if ($r + $rangePerChunk > Chunk::HASH_RANGE) {
                    $r = Chunk::HASH_RANGE;
                }
                $chunks[$r] = [ 'shard' => $shardId ];
                if ($r + $rangePerChunk > Chunk::HASH_RANGE) {
                    break;
                }
            }
        }
        $this->mapping->setChunks($chunks);
        return $chunks;
    }

    /**
     * Moves the records by the given shard key to the dest repository
     * progressively
     *
     * @param BaseRepo $srcRepo
     * @param BaseRepo $dstRepo
     * @param array $keys
     * @return Result[]
     */
    protected function migrateRecordsProgressively(BaseRepo $srcRepo, BaseRepo $dstRepo, array $keys)
    {
        $shardKey = $this->mapping->getKey();
        $select = $srcRepo->select();
        $select->where()->in($shardKey, $keys);
        $records = $select->fetch();
        return array_map(function($record) use ($dstRepo) {
            return $record->move($dstRepo);
        }, $records);
    }


    /**
     * Move a chunk
     */
    public function move($chunkIndex, $targetShardId, array $schemas)
    {
        $chunk = $this->mapping->loadChunk($chunkIndex);
        $shardId = $chunk->getShardId();
        if ($targetShardId === $shardId) {
            throw new InvalidArgumentException("$targetShardId == $shardId");
        }

        // we only care about the schemas related to the current shard mapping
        $schemas = SchemaUtils::filterShardMappingSchemas($this->mapping->id, $schemas);

        $shardKey = $this->mapping->getKey();
        $shards = $this->mapping->loadShardCollection();
        $hasher = $this->mapping->getHasher();

        // get shard Id of the chunk
        $srcShard = $chunk->loadShard();
        $dstShard = $shards[$targetShardId];

        $moved = [];
        foreach ($schemas as $schema) {
            // skip schemas that is global table.
            if ($schema->globalTable) {
                continue;
            }

            $repoClass = $schema->getRepoClass();
            $srcRepo = $srcShard->repo($repoClass);

            $keys = array_filter($srcRepo->fetchDistinctShardKeys(), function($k) use ($hasher, $chunk) {

                return $chunk->contains($hasher->hash($k));

            });

            if (!empty($keys)) {
                $dstRepo = $dstShard->repo($repoClass);
                $rets = $this->migrateRecordsProgressively($srcRepo, $dstRepo, $keys);
                $moved = array_merge($moved, $rets);
            }
        }
        return $moved;
    }

    public function split($chunkIndex, $n = 2)
    {
        $chunk = $this->mapping->loadChunk($chunkIndex);
        $range = $chunk->index - $chunk->from;
        $delta = intval(ceil($range / $n));

        $indexes = [];
        $index = $chunk->from;
        while (--$n) {
            $index += $delta;
            $this->mapping->insertChunk($index, $chunk->shardId);
            $indexes[] = $index;
        }
        return $indexes;
    }
}
