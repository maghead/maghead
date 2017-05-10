<?php

namespace Maghead\Manager;

use Exception;
use PDOException;
use InvalidArgumentException;
use PDO;
use ArrayAccess;
use Maghead\DSN\DSN;
use Maghead\DSN\DSNParser;
use Maghead\Runtime\Connection;
use Maghead\Runtime\Connector\PDOMySQLConnector;
use Maghead\Runtime\Connector\PDOConnector;

use ArrayObject;

class ConnectionManager extends ArrayObject
{
    /**
     * @var PDOConnection[] contains PDO connection objects (write)
     */
    protected $conns = [];

    /**
     * @var PDOConnection[] contains PDO connection object for read only purpose.
     */
    protected $reads = [];


    /**
     * Check if we have connected already.
     *
     * @param PDO    $conn pdo connection.
     * @param string $id   node source id.
     */
    public function has($nodeId)
    {
        return isset($this->conns[$nodeId]);
    }

    /**
     * Add connection.
     *
     * @param Connection $conn pdo connection
     * @param string     $id   node source id
     */
    public function add($nodeId, Connection $conn)
    {
        if (isset($this->conns[$nodeId])) {
            throw new Exception("$nodeId connection is already defined.");
        }
        $this->conns[$nodeId] = $conn;
    }


    /**
     * Share a write connection to the read-only connection.
     *
     * This method is used for sharing write connection (in-memory) to the read-only connection for testing.
     *
     * Note: this method shouldn't be used by application.
     *
     * @param string $nodeId
     */
    public function shareWrite($nodeId)
    {
        if (isset($this->conns[$nodeId])) {
            // copy write connection to read connection
            $this->reads[$nodeId] = $this->conns[$nodeId];
        }
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
        if (!isset($config['password'])) {
            $config['password'] = null;
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
        $this[ $id ] = $config;
    }

    public function hasNode($nodeId)
    {
        return isset($this[$nodeId]);
    }

    public function removeNode($id)
    {
        unset($this[$id]);
    }

    /**
     * Return node id(s).
     *
     * @return array key list
     */
    public function getNodeIds()
    {
        $nodeIds = [];
        foreach ($this as $nodeId) {
            $nodeIds[] = $nodeId;
        }
        return $nodeIds;
    }

    /**
     * Get datasource config.
     *
     * @return array
     */
    public function getNodeConfig($id)
    {
        if (isset($this[$id])) {
            return $this[ $id ];
        }
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
        if (isset($this->conns[$nodeId])) {
            return $this->conns[$nodeId];
        }
        return $this->conns[$nodeId] = $this->connect($nodeId);
    }


    public function getReadConnection($nodeId)
    {
        if (isset($this->reads[$nodeId])) {
            return $this->reads[$nodeId];
        }
        return $this->reads[$nodeId] = $this->connectRead($nodeId);
    }

    public function getWriteConnection($nodeId)
    {
        if (isset($this->conns[$nodeId])) {
            return $this->conns[$nodeId];
        }
        return $this->conns[$nodeId] = $this->connectWrite($nodeId);
    }

    /**
     * Connection to the instance without the dbname in the dsn string
     *
     * @param string $nodeId
     * @return Connection
     */
    public function connectInstance($nodeId)
    {
        $config = $this[$nodeId];
        $dsn = DSNParser::parse($config['dsn']);
        $dsn->removeDBName(); // works for pgsql and mysql only
        $config['dsn'] = $dsn->__toString();
        return PDOConnector::connect($config);
    }

    public function connectRead($nodeId)
    {
        if (!isset($this[$nodeId])) {
            $nodeIds = join(', ', $this->getNodeIds()) ?: '{none}';
            throw new InvalidArgumentException("data source {$nodeId} not found, valid nodes are {$nodeIds}");
        }
        $config = $this[$nodeId];
        if (isset($config['read'])) {
            // Implement a load balancer
            $idx = array_rand($config['read']);
            return PDOConnector::connect($config['read'][$idx]);
        }
        return PDOConnector::connect($config);
    }

    public function connectWrite($nodeId)
    {
        if (!isset($this[$nodeId])) {
            $nodeIds = join(', ', $this->getNodeIds()) ?: '{none}';
            throw new InvalidArgumentException("data source {$nodeId} not found, valid nodes are {$nodeIds}");
        }
        $config = $this[$nodeId];
        if (isset($config['write'])) {
            // Implement a load balancer
            $idx = array_rand($config['write']);
            return PDOConnector::connect($config['write'][$idx]);
        }
        return PDOConnector::connect($config);
    }

    public function connect($nodeId)
    {
        if (!isset($this[$nodeId])) {
            $nodeIds = join(', ', $this->getNodeIds()) ?: '{none}';
            throw new InvalidArgumentException("data source {$nodeId} not found, valid nodes are {$nodeIds}");
        }
        $config = $this[$nodeId];
        if (isset($config['write'])) {
            $idx = array_rand($config['write']);
            return PDOConnector::connect($config['write'][$idx]);
        }
        return PDOConnector::connect($config);
    }

    public function get($dsId)
    {
        return $this->getConnection($dsId);
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
        unset($this->conns[$sourceId]);
        unset($this->reads[$sourceId]);
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

    public function offsetUnset($name)
    {
        parent::offsetUnset($name);
        $this->close($name);
    }

    public function __destruct()
    {
        $this->free();
    }

    /**
     * free connections,
     * reset data sources.
     */
    public function free()
    {
        $this->closeAll();
        $this->conns = [];
        $this->reads = [];
        $this->exchangeArray([]);
    }

    public function clean()
    {
        $this->exchangeArray([]);
    }
}
