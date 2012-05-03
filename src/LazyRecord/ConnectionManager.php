<?php
namespace LazyRecord;
use Exception;
use PDO;
use ArrayAccess;


class ConnectionException extends Exception
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
 */

class ConnectionManager
    implements ArrayAccess
{
    public $datasources = array();

    public $conns = array();

    /**
     * Has connection ? 
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
     * @param PDO $conn pdo connection
     * @param string $id data source id
     */
    public function add($conn, $id = 'default' )
    {
        if( isset( $this->conns[ $id ] ) )
            throw new Exception( "$id connection is already defined." );
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
    public function addDataSource($id,$config)
    {
        $this->datasources[ $id ] = $config;
    }

    public function hasDataSource($id = 'default')
    {
        return isset($this->datasources[ $id ] );
    }

    public function getDataSource($id = 'default')
    {
        if( isset($this->datasources[ $id ] ) )
            return $this->datasources[ $id ];
    }

    public function getQueryDriver($id = 'default')
    {
        $self = $this;

        if( QueryDriver::hasInstance($id) ) {
            return QueryDriver::getInstance($id);
        }

        $driver = QueryDriver::getInstance($id);

        // configure query driver type
        if( $driverType = $this->getDataSourceDriver($id) ) {
            $driver->configure('driver',$driverType);
            $driver->quoter = function($string) use ($self,$id) {
                return $self->getConnection($id)->quote($string);
            };
        }

        // setup query driver options
        $config = isset($this->datasources[ $id ]) ? $this->datasources[ $id ] : null;
        if( $config && isset( $config['query_options'] ) ) {
            $queryOptions = $config['query_options'];
            foreach( $queryOptions as $option => $value ) {
                $driver->configure( $option , $value );
            }
        }

        // always use named parameter
        $driver->configure( 'placeholder', 'named' );
        return $driver;
    }

    public function getDataSourceDriver($id)
    {
        $config = $this->getDataSource($id);
        list($driverType) = explode( ':', $config['dsn'] );
        return $driverType;
    }

    /**
     * create connection
     *
     *    $dbh = new PDO('mysql:host=localhost;dbname=test', $user, $pass);
     *
     *    $pdo = new PDO( 
     *          'mysql:host=hostname;dbname=defaultDbName', 
     *          'username', 
     *          'password', 
     *          array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") 
     *    ); 
     *
     *    $dbh = new PDO('pgsql:dbname=$dbname; host=$host; username=$username; password=$password'); 
     *    $pdo = new PDO( 'sqlite::memory:', null, null, array(PDO::ATTR_PERSISTENT => true) );
     *                     sqlite2:mydb.sq2
     *
     */
    public function getConnection($sourceId = 'default')
    {
        if( isset($this->conns[$sourceId]) ) {
            return $this->conns[$sourceId];

        } elseif( isset($this->datasources[ $sourceId ] ) ) {
            $config = $this->datasources[ $sourceId ];
            $conn = new PDO( $config['dsn'],
                @$config['user'], 
                @$config['pass'], 
                @$config['connection_options'] );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // TODO: make this optional

            // $driver = $this->getQueryDriver($sourceId);
            // register connection to connection pool
            return $this->conns[ $sourceId ] = $conn;
        }

        throw new ConnectionException("data source $sourceId not found.");
    }

    public function getDefault()
    {
        return $this->getConnection('default');
    }

    static function getInstance()
    {
        static $instance;
        return $instance ?: $instance = new static;
    }

    public function close($sourceId)
    {
        if( $conn = $this->getConnection($sourceId) ) {
            $conn = null;
            unset( $this->conns[ $sourceId ] );
        }
    }

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
     */
    public function offsetSet($name,$value)
    {
        $this->conns[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->conns[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->conns[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->conns[$name]);
    }


    /**
     * get pdo connetion and make a query
     *
     * @param string $sql SQL statement
     */
    public function query($dsId,$sql)
    {
        $conn = $this->getConnection($dsId);
        return $conn->query( $sql );
    }

    public function prepareAndExecute($dsId,$sql,$args = array() )
    {
        $conn = $this->getConnection($dsId);
        $stm = $conn->prepare( $sql );
        $success = $stm->execute( $args );
        // if failed ?
        // if( false === $success ) {  }
        return $stm;
    }
    
}

