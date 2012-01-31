<?php
namespace LazyRecord;

/*
 * LazyRecord
 *     Connection Object
 * 
 *         - Connection object can switch logger.
 *             the logger can be set.
 * 
 *         - Model or Collection could get logger from their connection object. 
 * 
 * ModelLoader could be a part of LazyRecord::Loader
 * 
 * provide an interface to add model path.
 * 
 * XXX: please decide if we need to append "Model" keyword after a model.
 *  or just to use get_parent_class to check parent class (?)
 * 
 *
 * Usage:
 * 
 */
class Engine 
{
    public $lazy;

    /*
     * $conn
     *
     * Connection Objects
     *
     * contains:
     *    [ conn: , db_config , conn_config ],...
     *
     */
    var $conns = array(); // database connections
    var $classLoader;

    function __construct()
    {

    }

    static function getInstance()
    {
        static $instance;
        if( $instance )
            return $instance;
        return $instance = new static;
    }

    function getModelPaths() {
        return $this->classLoader->modelPaths;
    }

    function getClassLoader() {
        return $this->classLoader;
    }

    function connect( $backend , $dbConfig , $connConfig = array() ) 
    {
        if( $backend != 'mysqli' )
            throw new \Exception( "Unsupported backend '$backend'." );

        $connName = 'default';
        if( isset($connConfig['name']) ) 
        {
            $connName = $connConfig['name'];
        }

        /* check config here */
        if( ! $dbConfig )
            throw new \Exception( "Empty config for database." );

        /* init backend connection object */
        $className = "\\LazyRecord\\Backend\\$backend";
        if( ! class_exists( $className ) ) 
            throw new \Exception( "Unsupported backend, class not found. " . $className );

        /* register to connection hash */
        return $this->conns[ $connName ] = new $className( $dbConfig, $connConfig );
    }

    function hasConnection( $connName = 'default' ) 
    {
        return isset($this->conns[ $connName ]);
    }

    /* return connection */
    function connection( $connName = 'default' ) 
    {
        if( isset($this->conns[ $connName ]) )
            return $this->conns[ $connName ];
        throw new \Exception( "$connName connection handle is not defined." );
    }

    /* return all connections */
    function connections()
    {
        return $this->conns;
    }

    function close()
    {
        foreach( $this->conns as $name => $conn ) {
            if( $conn ) {
                # make sure connection is working
                $conn->close();
            }
            unset( $this->conns[ $name ] );
        }
    }

    function __destruct() 
    {
        $this->close();
    }
}

?>
