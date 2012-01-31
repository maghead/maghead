<?php
namespace LazyRecord\Backend;

use Exception;

/*
class DatabaseQueryException extends \Exception {
	public $sql;

	function __construct( $error, $sql = null ) {
		$this->sql = $sql;
	}

	function getSQL() 
	{
		return $this->sql;
	}
}
*/

/* 
    inherit from \mysqli extension
*/

class mysqli {

    /* connection object */
    public $handle;

    /* database config */
    public $dbConfig;

    /* connection config */
    public $connConfig;


    var $queryCnt = 0;

    function __construct( $config , $connConfig = array() )  {
        if( ! isset($config['host']) )
            $config['host'] = 'localhost';
        if( ! isset($config['charset'] ) )
            $config['charset'] = 'utf8';

        /* save config hash */
        $this->dbConfig = $config;
        $this->connConfig = $connConfig;

        if( ! $this->isLazy() )
            $this->handle = $this->doConnect();
    }

    function doConnect()
    {
        /* mysqli prototype:
         *
         * host, username, passwd, dbname, port, socket
         * */
        $config     = $this->dbConfig;
        $connConfig = $this->connConfig;
        $handle     = null;

        if( isset( $connConfig['no_select_db'] ) ) {
            $handle = new \mysqli( $config['host'], $config['user'], @$config['pass'], 
                null, @$config['port'] );
        } else {
            $handle = new \mysqli( $config['host'], $config['user'], @$config['pass'], 
                @$config['dbname'], @$config['port'] );
        }


        if($handle->connect_error)
            throw new Exception( "mysqli connect error (" . $handle->connect_errno . ") " . $handle->connect_error  );

        # echo 'Success... ' . $mysqli->host_info . "\n";
        if (!$handle->set_charset( $config['charset'] ))
            throw new Exception(  sprintf( "Error loading character set: %s", $handle->error ) );

        return $handle;
    }

    function isLazy()
    {
        return isset($this->connConfig['lazy']);
    }

    function handle()
    {
        if( ! $this->handle )
            return $this->handle = $this->doConnect();
        return $this->handle;
    }

    /*
        $result = $this->query( $sql , $param1, $param2 ... );
    */
    function query( $sql ) 
    {
        $num = func_num_args();
        if( $num > 1 ) {
			$func_args = func_get_args();
            for( $i = 1; $i < count($func_args) ; $i++ ) {

                // for string type args we should escape the string
                if( is_string( $func_args[ $i ] ) ) 
                    $func_args[$i] = $this->real_escape_string( $func_args[$i] );

            }
            $sql = call_user_func_array( 'sprintf', $func_args );
		}
        $result = $this->handle()->query( $sql );

        # $this->logger->write( $sql );
        $this->queryCnt++;

        if( $result == false ) {
			throw new Exception( $this->handle()->error . ":" . $sql  );
            # $this->logger->dieWith(  sprintf( "MySQLi Error: %s , %s" , $this->conn->error , $sql ) );
        }
        return $result;
    }

    function queryArrays( $sql ) {
        $rs = $this->query( $sql );
        $rows = array();
        while( $row = $rs->fetch_assoc() ) {
            array_push( $rows , $row );
        }
        $rs->close();
        return $rows;
    }

    function queryObject( $sql , $modelClass = null ) {
        $rs = $this->query( $sql );
        $obj = null;
        if( $modelClass )
            $obj = $rs->fetch_object( $modelClass );
        else
            $obj = $rs->fetch_object();
        $rs->close();
        return $obj;
    }

    function queryObjects( $sql , $modelClass = null ) {
        $items = array();
        $rs = $this->query( $sql );

        if( $modelClass ) {
            while( $m = $rs->fetch_object( $modelClass ) )
                array_push( $items , $m );
        } else {
            while( $m = $rs->fetch_object() )
                array_push( $items , $m );
        }
        $rs->close();
        return $items;
    }

    function queryValue( $sql ) { 
        $rs = $this->query( $sql );
        $data = $rs->fetch_array();
        $rs->close();
		if( $data )
			return array_pop($data);
    }

    function getStats() {
        return array( 
            "queryCnt" => $this->queryCnt
        );
    }

    function close()
    {
        # $this->handle()->close();
    }

}


?>
