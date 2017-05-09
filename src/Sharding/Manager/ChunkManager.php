<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\Chunk;
use Maghead\Sharding\Hasher\Hasher;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Runtime\Config\Config;
use Maghead\Runtime\BaseRepo;
use Maghead\Schema\SchemaUtils;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use LogicException;
use Exception;
use RuntimeException;
use ArrayIterator;
use Iterator;
use IteratorAggregate;
use InvalidArgumentException;

class MigrateException extends RuntimeException
{
}

class MigrateRecoveryException extends RuntimeException
{
}

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
    public function computeDistribution(array $shardIds, $numberOfChunks = 32, $range = 4294967296)
    {
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil($range / $numberOfChunks));
        return [
            "hashRange" => Chunk::MAX_KEY,
            "numberOfChunks" => $numberOfChunks,
            "rangePerChunk"  => $rangePerChunk,
            "chunksPerShard" => $chunksPerShard,
            "shards"         => $shardIds,
        ];
    }

    /**
     * Distribute the chunks.
     */
    public function distribute(array $shardIds, $numberOfChunks = 32)
    {
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil(Chunk::MAX_KEY / $numberOfChunks));

        $chunks = [];
        $r = 0;
        foreach ($shardIds as $shardId) {
            for ($i = 0 ; $i < $chunksPerShard; $i++) {
                $r += $rangePerChunk;
                if ($r + $rangePerChunk > Chunk::MAX_KEY) {
                    $r = Chunk::MAX_KEY;
                }
                $chunks[$r] = [ 'index' => $r, 'shard' => $shardId ];
                if ($r + $rangePerChunk > Chunk::MAX_KEY) {
                    break;
                }
            }
        }
        $this->mapping->setChunks($chunks);
        return $chunks;
    }

    /**
     * Steps of migrating a chunk
     *
     * 1) Clone the chunk to the dest shard.
     * 2) Verify the chunks
     * 3) Update chunk meta to the new shard ID
     * 4) Remove the old chunk
     */
    protected function verifyChunk(Chunk $chunk, array $schemas, callable $callback)
    {
        // TODO: implement this.
    }

    protected function removeChunk(Chunk $chunk, array $schemas, callable $callback)
    {
        // TODO: implement this
    }

    public function update(Chunk $chunk, Shard $dstShard, callback $callback)
    {
        // TODO: implement this
    }


    /**
     * process the chunk with the given callback.
     */
    protected function processChunk(Chunk $chunk, array $schemas, callable $callback)
    {
        $shardId = $chunk->getShardId();

        // we only care about the schemas related to the current shard mapping
        $schemas = SchemaUtils::filterShardMappingSchemas($this->mapping->id, $schemas);

        $shardKey = $this->mapping->getKey();
        $hasher = $this->mapping->getHasher();

        // get shard Id of the chunk
        $shard = $chunk->loadShard();

        $allRets = [];
        foreach ($schemas as $schema) {
            // skip schemas that is global table.
            if ($schema->globalTable) {
                continue;
            }

            $repoClass = $schema->getRepoClass();
            $repo = $shard->repo($repoClass);

            $keys = $this->selectChunkKeys($repo, $chunk, $hasher);
            if (!empty($keys)) {
                if ($rets = $callback($repo, $repoClass, $keys)) {
                    $allRets = array_merge($allRets, $rets);
                }
            }
        }

        return $allRets;
    }

    public function clone(Chunk $chunk, Shard $dstShard, array $schemas)
    {
        $shardId = $chunk->getShardId();
        if ($dstShard->id === $shardId) {
            throw new InvalidArgumentException("{$dstShard->id} == $shardId");
        }
        return $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
            $dstRepo = $dstShard->repo($repoClass);
            return $this->cloneRecords($srcRepo, $dstRepo, $keys);
        });
    }

    public function migrate(Chunk $chunk, Shard $dstShard, array $schemas)
    {
        $shardId = $chunk->getShardId();
        if ($dstShard->id === $shardId) {
            throw new InvalidArgumentException("{$dstShard->id} == $shardId");
        }

        try {
            $created = $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
                $dstRepo = $dstShard->repo($repoClass);
                return $this->cloneRecords($srcRepo, $dstRepo, $keys);
            });

            $missed = $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
                $dstRepo = $dstShard->repo($repoClass);
                return $this->verifyRecords($srcRepo, $dstRepo, $keys);
            });

            $deleted = $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
                $this->deleteRecords($srcRepo, $keys);
            });

            return $created;
        } catch (MigrateException $e) {
            return $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
                $dstRepo = $dstShard->repo($repoClass);
                $this->deleteRecords($dstRepo, $keys);
            });
        }
    }

    /**
     * Move a chunk
     */
    public function move(Chunk $chunk, Shard $dstShard, array $schemas)
    {
        $shardId = $chunk->getShardId();
        if ($dstShard->id === $shardId) {
            throw new InvalidArgumentException("{$dstShard->id} == $shardId");
        }
        return $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
            $dstRepo = $dstShard->repo($repoClass);
            return $this->migrateRecords($srcRepo, $dstRepo, $keys);
        });
    }

    /**
     * splits the chunk in the middle
     *
     * @param Chunk $chunk the index of the existing chunk
     * @return array created indexes.
     */
    public function split(Chunk $chunk, $n = 2)
    {
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

    protected function selectChunkKeys(BaseRepo $repo, Chunk $chunk, Hasher $hasher)
    {
        return array_filter($repo->fetchShardKeys(), function ($k) use ($hasher, $chunk) {
            return $chunk->contains($hasher->hash($k));
        });
    }

    protected function verifyRecords(BaseRepo $srcRepo, BaseRepo $dstRepo, array $keys)
    {
        $shardKey = $this->mapping->getKey();
        $select = $srcRepo->select();
        $select->where()->in($shardKey, $keys);
        $records = $select->fetch();

        $missed = [];
        foreach ($records as $record) {
            $key = $record->getGlobalPrimaryKey();
            $record = $dstRepo->findByGlobalPrimaryKey($key);
            if (!$record) {
                $ret = $dstRepo->import($record);
                if ($ret->error) {
                    throw new MigrateFailException;
                }
                $missed[] = $ret;
            }
        }

        return $missed;
    }

    protected function cloneRecords(BaseRepo $srcRepo, BaseRepo $dstRepo, array $keys)
    {
        $shardKey = $this->mapping->getKey();
        $select = $srcRepo->select();
        $select->where()->in($shardKey, $keys);
        $records = $select->fetch();

        $created = [];
        foreach ($records as $record) {
            $ret = $dstRepo->import($record);
            if ($ret->error) {
                throw new MigrateException;
            }
            $created[] = $ret;
        }

        return $created;
    }

    protected function deleteRecords(BaseRepo $repo, array $keys)
    {
        $shardKey = $this->mapping->getKey();
        $q = $repo->delete();
        $q->where()->in($shardKey, $keys);
        if (false === $q->execute()) {
            throw new MigrateRecoveryException;
        }
    }




    /**
     * Moves the records by the given shard key to the dest repository
     * PROGRESSIVELY.
     *
     * @param BaseRepo $srcRepo
     * @param BaseRepo $dstRepo
     * @param array $keys
     * @return Result[]
     */
    protected function migrateRecords(BaseRepo $srcRepo, BaseRepo $dstRepo, array $keys)
    {
        $shardKey = $this->mapping->getKey();
        $select = $srcRepo->select();
        $select->where()->in($shardKey, $keys);
        $records = $select->fetch();
        $rets = [];
        foreach ($records as $record) {
            $rets[] = $record->move($dstRepo);
        }
        return $rets;
    }
}
