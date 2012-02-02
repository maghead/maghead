<?php
namespace LazyRecord;
use Exception;

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
    public $conns = array();


    public function add($conn, $id = 'default' )
    {
        if( isset( $this->conns[ $id ] ) )
            throw new Exception( "$id connection is already defined." );

        $this->conns[ $id ] = $conn;
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
    public function create($podString)
    {
        // $this->conns[ $connection->id ] = $connection;
    }

    public function getDefault()
    {
        if( isset($this->conns[ 'default' ]) )
            return $this->conns[ 'default' ];
        throw new Exception( "Default connection not found." );
    }


    static function getInstance()
    {
        static $instance;
        return $instance ?: $instance = new static;
    }

}

