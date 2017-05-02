<?php

namespace Maghead\Sharding\Balancer\Policy;

use Maghead\Sharding\ShardCollection;

interface ShardBalancerPolicy {
    public function balance(ShardCollection $shards, array $chunks);
}


