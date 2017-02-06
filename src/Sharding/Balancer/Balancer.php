<?php

namespace Maghead\Sharding\Balancer;

interface Balancer
{
    public function select(array $nodes);
}
