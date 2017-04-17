<?php

namespace Maghead\Sharding\Hasher;

use Flexihash\Flexihash;
use Maghead\Sharding\ShardMapping;

class FlexihashHasher implements Hasher
{
    protected $mapping;

    protected $hash;

    public function __construct(ShardMapping $mapping)
    {
        $this->mapping = $mapping;
        $this->hash = new Flexihash;
        $this->hash->addTargets(array_keys($mapping->chunks));
    }

    /**
     * @param string $key
     *
     * @return string group id.
     */
    public function lookup($key)
    {
        return $this->hash->lookup($key);
    }
}
