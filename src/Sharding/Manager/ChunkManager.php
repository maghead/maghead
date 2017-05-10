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
    /**
     * @var ShardMapping
     */
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
        $div = intval(ceil(Chunk::MAX_KEY / $numberOfChunks));
        $chunks = [];
        $from = 0;
        foreach ($shardIds as $shardId) {
            for ($i = 0 ; $i < $chunksPerShard; $i++) {
                if ($from + $div >= Chunk::MAX_KEY) {
                    $chunks[] = [ 'from' => $from, 'index' => Chunk::MAX_KEY, 'shard' => $shardId ];
                    break 2;
                } else {
                    $chunks[] = [ 'from' => $from, 'index' => $from + $div, 'shard' => $shardId ];
                    $from += $div;
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
     * Insert a chunk into the chunk index.
     *
     * @param number $index the index of the existing chunk.
     */
    public function split(Chunk $chunk, $n = 2)
    {
        $index = $chunk->index;
        if ($index > Chunk::MAX_KEY) {
            throw new InvalidArgumentException("$index should be less than {Chunk::MAX_KEY}");
        }
        foreach ($this->mapping->chunks as $i => $c) {
            if ($c['index'] === $index) {
                $subchunks = [];
                $from = $c['from'];
                $range = $index - $from;
                $div   = intval(ceil($range / $n + 1));
                while ($n--) {
                    // echo "n: $n\n";
                    $subchunks[] = new Chunk($from, $from + $div, $c['shard'], $this->mapping->getDataSourceManager()); // use the same data source manager
                    $from += $div;
                }
                // echo "subchunks: ", count($subchunks) , "\n";
                $this->mapping->replaceChunk($i, $subchunks);
                return $subchunks;
            }
        }
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
