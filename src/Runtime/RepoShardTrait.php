<?php

namespace Maghead\Runtime;

trait RepoShardTrait
{
    /**
     * Fetches the distinct shard key in the repo.
     */
    public function fetchDistinctShardKeys()
    {
        $shardKey = static::SHARD_KEY;
        return $this->select("DISTINCT {$shardKey}")->fetchColumn(0);
    }
}
