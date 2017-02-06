<?php

namespace Maghead\Sharding\Hasher;

use Flexihash\Flexihash;
use Maghead\Sharding\ShardMapping;

class FlexihashHasher implements Hasher
{
    protected $mapping;

    protected $hash;

    protected $hashBy;

    public function __construct(ShardMapping $mapping)
    {
        $this->mapping = $mapping;
        $this->hashBy = $mapping->getHashBy();

        $this->hash = new Flexihash;
        $this->hash->addTargets(array_keys($this->hashBy));
    }

    /**
     * @param string $key
     *
     * @return string group id.
     */
    public function hash($key)
    {
        $target = $this->hash->lookup($key);
        return $this->hashBy[$target];
    }
}
