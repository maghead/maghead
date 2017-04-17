<?php

namespace Maghead\Sharding\Hasher;

interface Hasher
{
    public function lookup($key);
}
