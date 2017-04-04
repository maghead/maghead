<?php

namespace Maghead\DSN;

use ArrayAccess;
use Exception;

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

    public function getSocket()
    {
        return $this->getAttribute('unix_socket');
    }

    public function getUnixSocket()
    {
        return $this->getAttribute('unix_socket');
    }

    public function removeDBName()
    {
        return $this->removeAttribute('dbname');
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

    public function getDatabaseName()
    {
        if ($dbname = $this->getDBName()) {
            return $dbname;
        }
        // Parse the dbname from the DSN string
        if (!isset($this->arguments[0])) {
            throw new Exception("Can't find database name from the DSN string.");
        }
        $arg0 = $this->arguments[0];
        $file = basename($arg0);
        $parts = explode('.', $file);
        return $parts[0];
    }

    /**
     * Create a DSN object for write purpose.
     *
     * @return Maghead\DSN\DSN the created DSN object.
     */
    public static function createForWrite(array $config)
    {
        // extend from the read server
        if (isset($config['write'])) {
            $idx = array_rand($config['write']);
            $read = $config['write'][$idx];
            $c = array_merge($config, $read);
            return static::create($c);
        }
        return static::create($config);
    }

    /**
     * Create a DSN object for read purpose.
     *
     * @return Maghead\DSN\DSN the created DSN object.
     */
    public static function createForRead(array $config)
    {
        // extend from the read server
        if (isset($config['read'])) {
            $idx = array_rand($config['read']);
            $read = $config['read'][$idx];
            $c = array_merge($config, $read);
            return static::create($c);
        }
        return static::create($config);
    }

    /**
     * Convert a node config into a DSN object.
     *
     * @return Maghead\DSN\DSN the created DSN object.
     */
    public static function create(array $config)
    {
        // Build DSN connection string for PDO
        $dsn = new self($config['driver']);
        foreach (array('database', 'dbname') as $key) {
            if (isset($config[$key])) {
                $dsn->setAttribute('dbname', $config[$key]);
                break;
            }
        }

        if (isset($config['charset'])) {
            $dsn->setAttribute('charset', $config['charset']);
        }

        if (isset($config['unix_socket'])) {
            $dsn->setAttribute('unix_socket', $config['unix_socket']);
        } else {
            if (isset($config['host'])) {
                $dsn->setAttribute('host', $config['host']);
            }
            if (isset($config['port'])) {
                $dsn->setAttribute('port', $config['port']);
            }
        }
        return $dsn;
    }


}
