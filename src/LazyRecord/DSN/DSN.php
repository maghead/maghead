<?php
namespace LazyRecord\DSN;

class DSN
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

}



