<?php
namespace LazyRecord\DSN;
use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;

class DSN implements ArrayAccess, IteratorAggregate
{
    protected $driver;

    protected $attributes;

    protected $arguments;

    /**
     * The original DSN string
     */
    protected $dsn;

    public function __construct($driver, array $attributes = array(), array $arguments = array(), $dsn = null)
    {
        $this->driver = $driver;
        $this->attributes = $attributes;
        $this->arguments = $arguments;
        $this->dsn = $dsn;
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function __get($key)
    {
        return $this->attributes[$key];
    }

    public function __toString()
    {
        if ($this->dsn) {
            return $this->dsn;
        }
        $attrstrs = [];
        foreach ($this->attributes as $key => $val) {
            $attrstrs[] = $key . '=' . $val;
        }
        return $this->driver . ':' . join(';',$attrstrs);
    }


    
    public function offsetSet($key,$value)
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
    
    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

}



