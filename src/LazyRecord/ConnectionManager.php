<?php
namespace LazyRecord;
use Exception;

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
{
    public $datasources = array();

    public $conns = array();




    public function has($id)
    {
        return isset($this->conns[$id]);
    }

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
    public function create()
    {
        // $this->conns[ $connection->id ] = $connection;
    }

    public function getConnection($sourceId)
    {
        if( isset($this->conns[$sourceId]) ) {
            return $this->conns[$sourceId];
        } elseif( isset($this->datasources[ $sourceId ] ) ) {
            $config = $this->datasources[ $sourceId ];
            $conn = new \PDO( $config['dsn'], 
                @$config['user'] , 
                @$config['pass'] , 
                @$config['options']
            );

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
}

