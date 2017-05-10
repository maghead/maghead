<?php

namespace Maghead\Sharding;

use Maghead\Manager\DataSourceManager;

class Chunk
{

    /**
     * @var integer The default hash range 4294967296 = 2 ** 32
     */
    const MAX_KEY = 4294967296;

    const STATUS_OK = 1;

    /**
     * The chunk is current locked.
     */
    const STATUS_LOCKED = 1 << 1;

    /**
     * The chunk is currently migrating..
     */
    const STATUS_MIGRATING = 1 << 2;

    /**
     * The current is currently verifying..
     */
    const STATUS_VERIFYING = 1 << 3;

    /**
     * @var number The main index of the chunk. indexes below this index number
     *             should belongs to this Chunk.
     */
    public $index;

    /**
     * @var number The index where the chunk from
     */
    public $from;

    /**
     * @var string The ID of the shard
     */
    public $shardId;

    private $dataSourceManager;

    protected $status = self::STATUS_OK;

    /**
     * @param nubmer $index the chunk index
     * @param nubmer $from the index starts from. This number is less than $index.
     * @param string $shard the shard ID
     */
    public function __construct($index, $from, $shardId, DataSourceManager $dataSourceManager)
    {
        $this->index  = $index;
        $this->from   = $from;
        $this->shardId  = $shardId;
        $this->dataSourceManager = $dataSourceManager;
    }

    /**
     * Set status of the chunk.
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status of the chunk.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param number $index hashed index
     */
    public function contains($index)
    {
        // echo "key($k) -> index($index): {$this->from} < {$index} && {$index} <= {$this->index} \n";
        return $this->from < $index && $index <= $this->index;
    }

    public function loadShard()
    {
        return new Shard($this->shardId, $this->dataSourceManager);
    }

    /**
     * Returns the shard ID
     */
    public function getShardId()
    {
        return $this->shardId;
    }

    public function toArray()
    {
        return [
            'from' => $this->from, // just cache, this shouldn't be used for now.
            'index' => $this->index,
            'status' => $this->status,
            'shard' => $this->shardId,
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function __debugInfo()
    {
        return [
            'shardId' => $this->shardId,
            'from' => $this->from, // just cache, this shouldn't be used for now.
            'index' => $this->index,
            'status' => $this->status,
        ];
    }
}
