<?php
namespace LazyRecord;
use Exception;
use PDOException;
use PDO;
use LazyRecord\Adapter\PdoAdapter;
use ArrayAccess;
use LazyRecord\Connection;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PDODriverFactory;

class SQLQueryException extends Exception 
{
    public $args = array();

    public $sql;

    public function __construct($dsId, $sql, $args, Exception $e) {
        parent::__construct( 'SQL Query Error at "' . $dsId . '" data source, Message: ' . $e->getMessage() , 0 , $e);
        $this->sql = $sql;
        $this->args = $args;
    }
}

class UndefinedDataSourceException extends Exception
{

}

/**
 * Connection Manager
 *
 *    $connManager = ConnectionManager::getInstance();
 *    $conn = $connManager->create( '{{id}}', '' );
 *
 *    $conn = $connManager->default(); // return PDO connection object 
 *
 *    $result = $conn->query( );
 *    $stm = $conn->prepare( );
 *    $stm->execute( );
 *
 *    foreach( $connManager as $dataSourceId => $dataSourceConfig ) {
 *
 *    }
 */

class ConnectionManager
    implements ArrayAccess
{
    const DEFAULT_DS = 'default';

    /**
     * @var array contains data source configurations
     */
    public $datasources = array();

    /**
     * @var PDOConnection[] contains PDO connection objects.
     */
    public $conns = array();

    /**
     * Check if we have connected already
     *
     * @param PDO $conn pdo connection.
     * @param string $id data source id.
     */
    public function has($id)
    {
        return isset($this->conns[$id]);
    }


    /**
     * Add connection
     *
     * @param Connection $conn pdo connection
     * @param string $id data source id
     */
    public function add(Connection $conn, $id = 'default' )
    {
        if (isset( $this->conns[ $id ] )) {
            throw new Exception( "$id connection is already defined." );
        }
        $this->conns[ $id ] = $conn;
    }




    /**
     * Add custom data source:
     *
     * source config:
     *
     * @param string $id data source id
     * @param string $config data source config
     */
    public function addDataSource($id, array $config)
    {
        if (!isset($config['connection_options'])) {
            $config['connection_options'] = array();
        }
        if (!isset($config['user'])) {
            $config['user'] = NULL;
        }
        if (!isset($config['pass'])) {
            $config['pass'] = NULL;
        }
        if (!isset($config['query_options'])) {
            $config['query_options'] = array();
        }
        if (!isset($config['driver'])) {
            if (isset($config['dsn']) ) {
                list($driver) = explode( ':', $config['dsn'], 2);
                $config['driver'] = $driver;
            }
        }
        $this->datasources[ $id ] = $config;
    }

    public function hasDataSource($id = 'default')
    {
        return isset($this->datasources[ $id ] );
    }

    public function removeDataSource($id) 
    {
        unset( $this->datasource[$id] );
    }

    /**
     * Return datasource id(s)
     *
     * @return array key list
     */
    public function getDataSourceIdList()
    {
        return array_keys($this->datasources);
    }


    /**
     * Get datasource config
     *
     * @return array
     */
    public function getDataSource($id = 'default')
    {
        if( isset($this->datasources[ $id ] ) )
            return $this->datasources[ $id ];
    }


    /**
     * Get SQLBuilder\QueryDriver by data source id.
     *
     * @param string $id datasource name
     * @return LazyRecord\QueryDriver
     */
    public function getQueryDriver($id = 'default')
    {
        $self = $this;
        return $this->getConnection($id)->createQueryDriver();
    }

    public function getDriverType($id)
    {
        $config = $this->getDataSource($id);
        return $config['driver'];
    }

    /**
     * Create connection
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
     *
     */
    public function getConnection($sourceId = 'default')
    {
        // use cached connection objects
        if( isset($this->conns[$sourceId]) ) {
            return $this->conns[$sourceId];
        } 
        if (!isset($this->datasources[ $sourceId ])) {
            throw new UndefinedDataSourceException("data source $sourceId not found.");
        }
        $config = $this->datasources[ $sourceId ];
        $conn = new Connection($config['dsn'], $config['user'], $config['pass'], $config['connection_options']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // TODO: can we make this optional ?
        return $this->conns[ $sourceId ] = $conn;
    }

    public function get($dsId)
    {
        return $this->getConnection($dsId);
    }


    /**
     * Get default data source id
     *
     * @return string 'default'
     */
    public function getDefaultConnection()
    {
        return $this->getConnection(self::DEFAULT_DS);
    }


    /**
     * Get singleton instance
     */
    static function getInstance()
    {
        static $instance;
        return $instance ? $instance : $instance = new static;
    }


    /**
     * Close connection
     */
    public function close($sourceId)
    {
        if( isset($this->conns[ $sourceId ]) ) {
            $this->conns[ $sourceId ] = null;
            unset( $this->conns[ $sourceId ] );
        }
    }


    /**
     * Close all connections
     */
    public function closeAll()
    {
        foreach( $this->conns as $id => $conn ) {
            $this->close( $id );
        }
    }

    /**
     * free connections,
     * reset data sources
     */
    public function free()
    {
        $this->closeAll();
        $this->datasources = array();
        $this->conns = array();
    }



    /**
     * ArrayAccess interface
     *
     * @param string $name
     * @param Connection $value
     */
    public function offsetSet($name, $value)
    {
        if (! $value instanceof Connection) {
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

    /**
     *
     */
    public function offsetUnset($name)
    {
        $this->close($name);
    }


    /**
     * get pdo connetion and make a query
     *
     * @param string $sql SQL statement
     */
    public function query($dsId,$sql)
    {
        return $this->getConnection($dsId)->query( $sql );
    }

    public function prepareAndExecute($dsId,$sql,$args = array() )
    {
        $stm = NULL;
        try {
            $conn = $this->getConnection($dsId);
            $stm = $conn->prepareAndExecute($sql, $args);
        } catch( PDOException $e ) {
            throw new SQLQueryException($dsId,$sql,$args,$e);
        }
        // if failed ?
        // if( false === $success ) {  }
        return $stm;
    }

    public function __destruct() 
    {
        $this->free();
    }
}
