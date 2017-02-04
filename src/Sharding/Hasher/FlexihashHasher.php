<?php

namespace Maghead\Sharding\Hasher;

use Flexihash\Flexihash;

class FlexihashHasher implements Hasher
{
    protected $mapping;

    protected $hash;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
        $this->hash = new Flexihash;
        $this->hash->addTargets(array_keys($mapping['hash']));
    }

    /**
     * @param string $key
     *
     * @return string group id.
     */
    public function hash($key)
    {
        $target = $this->hash->lookup($key);
        return $this->mapping['hash'][$target];
    }
}
