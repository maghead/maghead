<?php

namespace Maghead\DSN;

use ArrayAccess;

/**
 * Data object for DSN information.
 *
 * getHost(), getPort(), getDBName() methods are used by MySQL and PostgreSQL
 */
class DSN implements ArrayAccess
{
    /**
     * @var string
     */
    protected $driver;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * The original DSN string.
     */
    protected $originalDSN;

    public function __construct($driver, array $attributes = array(), array $arguments = array(), $originalDSN = null)
    {
        $this->driver = $driver;
        $this->attributes = $attributes;
        $this->arguments = $arguments;
        $this->originalDSN = $originalDSN;
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __toString()
    {
        return $this->driver.':'.$this->getAttributeString();
    }

    public function getAttributeString()
    {
        $attrstrs = [];
        foreach ($this->attributes as $key => $val) {
            $attrstrs[] = $key.'='.$val;
        }

        return implode(';', $attrstrs);
    }

    public function offsetSet($key, $value)
    {
        $this->attributes[ $key ] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->attributes[ $key ]);
    }

    public function offsetGet($key)
    {
        return $this->attributes[ $key ];
    }

    public function offsetUnset($key)
    {
        unset($this->attributes[$key]);
    }

    public function setAttribute($key, $val)
    {
        $this->attributes[$key] = $val;
    }

    public function removeAttribute($key)
    {
        unset($this->attributes[$key]);
    }

    public function getAttribute($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getHost()
    {
        return $this->getAttribute('host');
    }

    public function getPort()
    {
        return $this->getAttribute('port');
    }

    public function getDBName()
    {
        return $this->getAttribute('dbname');
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function getOriginalDSN()
    {
        return $this->originalDSN;
    }
}
