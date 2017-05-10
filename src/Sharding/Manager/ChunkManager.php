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
use Iterator;
use IteratorAggregate;
use InvalidArgumentException;
use ArrayObject;

class MigrateException extends RuntimeException {}

class MigrateRecoveryException extends MigrateException {}

class MigrateCloneFailedException extends MigrateException {

    protected $record;

    function __construct($record, $message = '', $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->record = $record;
    }

}

class MigrateResult extends ArrayObject {

    function __construct(array $result)
    {
        parent::__construct($result, ArrayObject::ARRAY_AS_PROPS);
    }

    public function isSuccessful() {
        return $this instanceof SucceededMigrateResult;
    }
}

class SucceededMigrateResult extends MigrateResult {}

class FailedMigrateResult extends MigrateResult {}

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
     * Verify the chunk in the original shard with the chunk in dstShard
     */
    public function verify(Chunk $chunk, Shard $dstShard, array $schemas)
    {
        $missed = $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
            $dstRepo = $dstShard->repo($repoClass);
            return $this->verifyRecords($srcRepo, $dstRepo, $keys);
        });

        return $missed;
    }

    /**
     * Remove the chunk range in the given shard instead of the shard the chunk belons to.
     *
     * @param Chunk $chunk
     * @param Shard $dstShard
     * @param array $schemas
     */
    public function removeFrom(Chunk $chunk, Shard $dstShard, array $schemas)
    {
        return $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) use ($dstShard) {
            $dstRepo = $dstShard->repo($repoClass);

            return $this->deleteRecords($dstRepo, $keys);
        });
    }

    public function remove(Chunk $chunk, array $schemas)
    {
        $deleted = $this->processChunk($chunk, $schemas, function ($srcRepo, $repoClass, $keys) {

            return $this->deleteRecords($srcRepo, $keys);
        });

        return $deleted;
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
                    if (is_array($rets)) {
                        $allRets = array_merge($allRets, $rets);
                    } else {
                        $allRets[] = $rets;
                    }
                }
            }
        }

        return $allRets;
    }

    /**
     * Clone the record in the chunk range from the original shard to the dstShard.
     */
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

            $created = $this->clone($chunk, $dstShard, $schemas);

            $missed = $this->verify($chunk, $dstShard, $schemas);

            $deleted = $this->remove($chunk, $schemas);

            return new SucceededMigrateResult([
                'created' => $created,
                'missed'  => $missed,
                'deleted' => $deleted,
            ]);

        } catch (MigrateException $e) {

            $deleted = $this->removeFrom($chunk, $dstShard, $schemas);

            return new FailedMigrateResult([ 'deleted' => $deleted ]);

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
        $q = $srcRepo->select();
        $q->where()->in($shardKey, $keys);
        $records = $q->fetch();

        $created = [];
        foreach ($records as $record) {
            $ret = $dstRepo->import($record);
            if ($ret->error) {
                throw new MigrateCloneFailedException($record, $ret->message);
            }
            $created[] = $ret;
        }

        return $created;
    }

    /**
     * Delete the records and return the number of deletion.
     *
     * @return number the number of deletion
     */
    protected function deleteRecords(BaseRepo $repo, array $keys)
    {
        $shardKey = $this->mapping->getKey();
        $q = $repo->delete();
        $q->where()->in($shardKey, $keys);

        list($ret, $stm) = $q->execute();
        if ($ret === false) {
            throw new MigrateRecoveryException;
        }

        return $stm->rowCount(); // how many records are deleted.
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
