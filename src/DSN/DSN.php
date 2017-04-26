<?php

namespace Maghead\DSN;

use ArrayAccess;
use Exception;
use PDO;

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
     * Convert a node config into a DSN object.
     *
     * @return Maghead\DSN\DSN the created DSN object.
     */
    public static function create(array $config)
    {
        // Build DSN connection string for PDO
        $dsn = new self($config['driver']);

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

        foreach (array('database', 'dbname') as $key) {
            if (isset($config[$key])) {
                $dsn->setAttribute('dbname', $config[$key]);
                break;
            }
        }

        if (isset($config['charset'])) {
            $dsn->setAttribute('charset', $config['charset']);
        }


        return $dsn;
    }

    /**
     * Convert DSN to node config
     *
     * @return array the node config.
     */
    public function toConfig()
    {
        $node = [];
        $node['driver'] = $this->getDriver();

        // Copy the configuration from DSN to node config
        if ($host = $this->getHost()) {
            $node['host'] = $host;
        }

        if ($port = $this->getPort()) {
            $node['port'] = $port;
        }
        if ($socket = $this->getUnixSocket()) {
            $node['unix_socket'] = $socket;
        }
        // MySQL/PgSQL only attribute
        if ($dbname = $this->getAttribute('dbname')) {
            $node['database'] = $dbname;
        }

        switch ($this->getDriver()) {
            case 'mysql':
                // $this->logger->debug('Setting connection options: PDO::MYSQL_ATTR_INIT_COMMAND');
                $node['connection_options'] = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];
            break;
        }

        return $node;
    }

    /**
     * Update the DSN string base on the given node config.
     *
     * @return array the node config.
     */
    public static function update(array $nodeConfig)
    {
        $nodeConfig['dsn'] = DSN::create($nodeConfig)->__toString();
        return $nodeConfig;
    }
}
