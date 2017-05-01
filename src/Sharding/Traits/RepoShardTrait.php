<?php

namespace Maghead\Sharding\Traits;

use PDO;

use Maghead\Sharding\ShardKeyStat;

trait RepoShardTrait
{
    /**
     * Fetches the distinct shard key in the repo.
     */
    public function fetchShardKeys()
    {
        $shardKey = static::SHARD_KEY;
        return $this->select("DISTINCT {$shardKey}")->fetchColumn(0);
    }

    /**
     * Fetch the stats of each shard key
     *
     * This method returns an array of object that has "key" property and "cnt_of_rows" property.
     *
     * @return array
     */
    public function fetchShardKeyStats()
    {
        $shardKey = static::SHARD_KEY;
        $table = $this->getTable();
        $stm = $this->write->prepare("SELECT {$shardKey} AS shardKey, COUNT({$shardKey}) AS numberOfRows FROM {$table} GROUP BY shardKey ORDER BY numberOfRows ASC");
        $stm->setFetchMode(PDO::FETCH_CLASS, 'Maghead\\Sharding\\ShardKeyStat', [$this]);
        $stm->execute();
        return $stm->fetchAll();
    }
}
