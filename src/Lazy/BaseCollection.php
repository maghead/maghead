<?php
namespace Lazy;
use PDO;
use PDOException;
use Exception;
use Iterator;

use SQLBuilder\QueryBuilder;
use Lazy\OperationResult\OperationSuccess;
use Lazy\OperationResult\OperationError;
use Lazy\ConnectionManager;


/**
 * base collection class
 */
class BaseCollection
    implements Iterator
{

    protected $lastSQL;

    /**
     * @var SQLBuilder\QueryBuilder
     */
    protected $currentQuery;

    /**
     * @var PDOStatement handle
     */
    protected $handle;


    /**
     * handle data for items
     *
     * @var array
     */ 
    protected $itemData = null;


    /**
     * current data item cursor position
     *
     * @var integer
     */
    protected $itemCursor = null;

    public function __construct() {
        // init a query
    }


    public function __get( $key ) 
    {
        /**
         * lazy attributes
         */
        if( $key == '_schema' ) {
            return SchemaLoader::load( static::schema_proxy_class );
        }
        elseif( $key == '_handle' ) {
            return $this->handle ?: $this->fetch();
        }
        elseif( $key == '_query' ) {
            return $this->currentQuery ?: $this->createQuery();
        }
        elseif( $key == '_items' ) {
            return $this->itemData ?: $this->_readRows();
        }
    }

    public function free()
    {
        $this->itemData = null;
        return $this;
    }

    public function __call($m,$a)
    {
        $q = $this->_query;
        if( method_exists($q,$m) ) {
            return call_user_func_array(array($q,$m),$a);
        }
    }

    public function createQuery()
    {
        $q = new QueryBuilder;
        $q->driver = $this->getCurrentQueryDriver();
        $q->table( $this->_schema->table );
        $q->select('*');
        $q->alias('m'); // main table alias
        return $this->currentQuery = $q;
    }



    /**
     * fetch data from database, and catch the handle.
     *
     * Which calls doFetch() to do a query operation.
     */
    public function fetch($force = false)
    {
        if( $this->handle == null || $force ) {
            $ret = $this->doFetch();
        }
        return $this->handle;
    }



    /**
     * Build sql from current query and make a query to database.
     *
     * @return OperationResult
     */
    public function doFetch()
    {
        /* fetch by current query */
        $query = $this->_query;
        $this->lastSQL = $sql = $query->build();
        $vars = $query->vars;

        // XXX: here we use SQLBuilder\QueryBuilder to build our variables,
        //   but PDO doesnt accept boolean type value, we need to transform it.
        foreach( $vars as $k => & $v ) {
            if( $v === false )
                $v = 'FALSE';
            elseif( $v === true )
                $v = 'TRUE';
        }

        try {
            $this->handle = $this->dbPrepareAndExecute($sql, $vars );
        }
        catch ( Exception $e )
        {
            return new OperationError( 'Collection fetch failed: ' . $e->getMessage() , array( 
                'vars' => $vars,
                'sql' => $sql,
                'exception' => $e,
            ));
        }
        return new OperationSuccess('Updated', array( 'sql' => $sql ));
    }


    /**
     * get selected item size
     *
     * @return integer size
     */
    public function size()
    {
        return count($this->_items);
    }


    /**
     * Get selected items and wrap it into a CollectionPager object
     *
     * @return CollectionPager
     */
    public function pager($page = 1,$pageSize = 10)
    {
        return new CollectionPager( $this->_items, $page, $pageSize );
    }


    /**
     * Get items
     */
    public function items()
    {
        return $this->_items;
    }

    public function getCurrentQueryDriver()
    {
        return ConnectionManager::getInstance()->getQueryDriver( $this->getDataSourceId() );
    }

    // xxx: should retrieve id from _schema class.
    public function getDataSourceId()
    {
        return 'default';
    }


    /**
     * Read rows from database handle
     *
     * @return model_class[]
     */
    protected function _readRows()
    {
        $h = $this->_handle;

        if( $h === null )
            throw new Exception( 'Empty handle: ' . static::model_class );

        $this->itemData = array();
        while( $o = $h->fetchObject( static::model_class ) ) {
            $o->deflate();
            $this->itemData[] = $o;
        }
        return $this->itemData;
    }





    /**
     * Implements Iterator methods 
     */
    public function rewind()
    { 
        $this->itemCursor = 0;
    }

    /* is current row a valid row ? */
    public function valid()
    {
        if( $this->itemData == null )
            $this->_readRows();
        return isset($this->itemData[ $this->itemCursor ] );
    }

    public function current() 
    { 
        return $this->itemData[ $this->itemCursor ];
    }

    public function next() 
    {
        return $this->itemData[ $this->itemCursor++ ];
    }

    public function key()
    {
        return $this->itemCursor;
    }


    public function toArray()
    {
        $array = array();
        $items = $this->items();
        foreach( $items as $item ) {
            $array[] = $item->toArray();
        }
        return $array;
    }




    /*******************************************
     * XXX: duplicate methods from BaseModel 
     * *****************************************/

    /**
     * get pdo connetion and make a query
     *
     * @param string $sql SQL statement
     */
    public function dbQuery($sql)
    {
        $conn = $this->getConnection();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // $conn->setAttribute(PDO::ATTR_AUTOCOMMIT,true);
        $stm = $conn->prepare( $sql );
        $stm->execute();
        return $stm;
    }

    public function dbPrepareAndExecute($sql,$args = array() )
    {
        // var_dump( $sql, $args ); 
        $conn = $this->getConnection();
        $stm = $conn->prepare( $sql );
        $stm->execute( $args );
        return $stm;
    }

    /**
     * get default connection object (PDO) from connection manager
     *
     * @return PDO
     */
    public function getConnection()
    {
        // xxx: get data source id from schema.
        $sourceId = 'default';
        $connManager = ConnectionManager::getInstance();
        return $connManager->getDefault(); // xxx: support read/write connection later
    }


    public function getLastSQL()
    {
        return $this->lastSQL;
    }

}

