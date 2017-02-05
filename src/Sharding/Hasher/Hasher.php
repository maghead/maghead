<?php

namespace Maghead\Sharding\Hasher;

interface Hasher
{
    public function hash($key);
}
