<?php

namespace Maghead\Manager;

use Exception;
use PDOException;
use InvalidArgumentException;
use PDO;
use ArrayAccess;
use Maghead\DSN\DSN;
use Maghead\Connection;
use Maghead\Connector\PDOMySQLConnector;

class ConnectionManager implements ArrayAccess
{
    const DEFAULT_DS = 'default';

    protected $masterNodeId;

    /**
     * @var array contains node configurations
     */
    protected $nodeConfigurations = array();

    /**
     * @var PDOConnection[] contains PDO connection objects.
     */
    protected $conns = array();

    /**
     * Check if we have connected already.
     *
     * @param PDO    $conn pdo connection.
     * @param string $id   node source id.
     */
    public function has($id)
    {
        return isset($this->conns[$id]);
    }

    /**
     * Add connection.
     *
     * @param Connection $conn pdo connection
     * @param string     $id   node source id
     */
    public function add(Connection $conn, $id = 'default')
    {
        if (isset($this->conns[ $id ])) {
            throw new Exception("$id connection is already defined.");
        }
        $this->conns[ $id ] = $conn;
    }

    /**
     * Add custom node config
     *
     * source config:
     *
     * @param string $id     node id
     * @param string $config node config
     */
    public function addNode($id, array $config)
    {
        if (!isset($config['connection_options'])) {
            $config['connection_options'] = array();
        }
        if (!isset($config['user'])) {
            $config['user'] = null;
        }
        if (!isset($config['pass'])) {
            $config['pass'] = null;
        }
        if (!isset($config['query_options'])) {
            $config['query_options'] = array();
        }
        if (!isset($config['driver'])) {
            if (isset($config['dsn'])) {
                list($driver) = explode(':', $config['dsn'], 2);
                $config['driver'] = $driver;
            }
        }
        $this->nodeConfigurations[ $id ] = $config;
    }

    public function hasNode($id = 'default')
    {
        return isset($this->nodeConfigurations[ $id ]);
    }

    public function removeNode($id)
    {
        unset($this->nodeConfigurations[$id]);
    }

    /**
     * Return node id(s).
     *
     * @return array key list
     */
    public function getNodeIds()
    {
        return array_keys($this->nodeConfigurations);
    }

    /**
     * Get datasource config.
     *
     * @return array
     */
    public function getNodeConfig($id)
    {
        if (isset($this->nodeConfigurations[ $id ])) {
            return $this->nodeConfigurations[ $id ];
        }
    }

    public function getMasterNodeConfig()
    {
        return $this->getNodeConfig($this->masterNodeId ?: self::DEFAULT_DS);
    }

    public function setMasterNodeId($nodeId)
    {
        $this->masterNodeId = $nodeId;
    }

    /**
     * Get SQLBuilder\QueryDriver by data source id.
     *
     * @param string $id datasource name
     *
     * @return Maghead\QueryDriver
     */
    public function getQueryDriver($id)
    {
        return $this->getConnection($id)->getQueryDriver();
    }

    public function getDriverType($id)
    {
        $config = $this->getNodeConfig($id);

        return $config['driver'];
    }


    /**
     * Create connection.
     *
     *    $dbh = new Connection('mysql:host=localhost;dbname=test', $user, $pass);
     *
     *    $pdo = new Connection(
     *          'mysql:host=hostname;dbname=defaultDbName',
     *          'username',
     *          'password',
     *          array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
     *    );
     *
     *    $dbh = new Connection('pgsql:dbname=$dbname; host=$host; username=$username; password=$password');
     *    $pdo = new Connection( 'sqlite::memory:', null, null, array(PDO::ATTR_PERSISTENT => true) );
     *                     sqlite2:mydb.sq2
     */
    public function getConnection($nodeId)
    {
        if ($nodeId === 'default') {
            $nodeId = $this->masterNodeId;
        }
        // use cached connection objects
        if (isset($this->conns[$nodeId])) {
            return $this->conns[$nodeId];
        }
        return $this->conns[$nodeId] = $this->connect($nodeId);
    }

    public function connect($nodeId)
    {
        if ($nodeId === 'default') {
            $nodeId = $this->masterNodeId;
        }
        if (!isset($this->nodeConfigurations[$nodeId])) {
            throw new InvalidArgumentException("data source {$nodeId} not found.");
        }
        $config = $this->nodeConfigurations[$nodeId];
        return Connection::connect($config);
    }

    public function get($dsId)
    {
        return $this->getConnection($dsId);
    }

    /**
     * Get default data source id.
     *
     * @return string 'default'
     */
    public function getMasterConnection()
    {
        return $this->getConnection($this->masterNodeId ?: self::DEFAULT_DS);
    }

    /**
     * Get singleton instance.
     */
    public static function getInstance()
    {
        static $instance;
        if ($instance) {
            return $instance;
        }

        return $instance = new static();
    }

    /**
     * Close connection.
     */
    public function close($sourceId)
    {
        if (isset($this->conns[$sourceId])) {
            $this->conns[$sourceId] = null;
            unset($this->conns[$sourceId]);
        }
    }

    /**
     * Close all connections.
     */
    public function closeAll()
    {
        foreach ($this->conns as $id => $conn) {
            $this->close($id);
        }
    }

    /**
     * free connections,
     * reset data sources.
     */
    public function free()
    {
        $this->closeAll();
        $this->nodeConfigurations = [];
        $this->conns = [];
    }

    /**
     * ArrayAccess interface.
     *
     * @param string     $name
     * @param Connection $value
     */
    public function offsetSet($name, $value)
    {
        if (!$value instanceof Connection) {
            throw new InvalidArgumentException('$value is not a Connection object.');
        }
        $this->conns[ $name ] = $value;
    }

    /**
     * Check if a connection exists.
     *
     * @param string $name
     */
    public function offsetExists($name)
    {
        return isset($this->conns[ $name ]);
    }

    /**
     * Get connection by data source id.
     *
     * @param string $name
     */
    public function offsetGet($name)
    {
        return $this->getConnection($name);
    }

    public function offsetUnset($name)
    {
        $this->close($name);
    }

    public function __destruct()
    {
        $this->free();
    }
}
