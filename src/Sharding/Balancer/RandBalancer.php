<?php

namespace Maghead\Sharding\Balancer;

class RandBalancer implements Balancer
{
    public function select(array $nodes)
    {
        return array_rand($nodes);
    }
}
