<?php
namespace LazyRecord;
use PDO;
use PDOException;
use Exception;
use Iterator;
use ArrayAccess;

use SQLBuilder\QueryBuilder;
use LazyRecord\OperationResult\OperationSuccess;
use LazyRecord\OperationResult\OperationError;
use LazyRecord\ConnectionManager;
use LazyRecord\Schema\SchemaLoader;


/**
 * base collection class
 */
class BaseCollection
    implements Iterator, ArrayAccess, ExporterInterface
{

    protected $_lastSql;

    protected $_vars;

    /**
     * @var SQLBuilder\QueryBuilder
     */
    protected $_currentQuery;

    /**
     * @var PDOStatement handle
     */
    protected $handle;


    /**
     * handle data for items
     *
     * @var array
     */ 
    protected $_itemData = null;



    /**
     * preset vars for creating
     */
    protected $_presetVars = array();



    /**
     * postCreate hook
     */
    protected $_postCreate;


    /**
     * current data item cursor position
     *
     * @var integer
     */
    protected $_itemCursor = null;



    /**
     * operation result object
     */
    protected $_result;


    protected $_alias = 'm';

    protected $_explictSelect = false;


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
            return $this->handle ?: $this->prepareData();
        }
        elseif( $key == '_query' ) {
            return $this->_currentQuery ?: $this->createQuery();
        }
        elseif( $key == '_items' ) {
            return $this->_itemData ?: $this->_readRows();
        }
    }

    public function free()
    {
        $this->_itemData = null;
        return $this;
    }

    public function __call($m,$a)
    {
        $q = $this->_query;
        if( method_exists($q,$m) ) {
            return call_user_func_array(array($q,$m),$a);
        }
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function setAlias($alias)
    {
        $this->_alias = $alias;
        return $this;
    }

    public function setExplictSelect($boolean = true)
    {
        $this->_explictSelect = $boolean;
        return $this;
    }

    public function createQuery()
    {
        $q = new QueryBuilder;
        $q->driver = $this->getCurrentQueryDriver();
        $q->table( $this->_schema->table );
        $q->select(
            $this->_explictSelect 
                ? $this->getExplicitColumnSelect($q->driver)
                : '*'
        );
        $q->alias( $this->getAlias() ); // main table alias
        return $this->_currentQuery = $q;
    }


    // xxx: this might be used in other join statements.
    public function getExplicitColumnSelect($driver)
    {
        $alias = $this->getAlias();
        return array_map( function($name) use($alias,$driver) { 
                return $alias . '.' . $driver->getQuoteColumn( $name );
        }, $this->_schema->getColumnNames());
    }

    /**
     * prepare data handle, call fetch method to read data from database, and 
     * catch the handle.
     *
     * Which calls doFetch() to do a query operation.
     */
    public function prepareData($force = false)
    {
        if( $this->handle == null || $force ) {
            $this->_result = $this->fetch();
        }
        return $this->handle;
    }



    /**
     * Fetch Build sql from current query and make a query to database.
     *
     * @return OperationResult
     */
    public function fetch()
    {
        /* fetch by current query */
        $query = $this->_query;
        $this->_lastSql = $sql = $query->build();
        $this->_vars = $vars = $query->vars;


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
            throw new Exception( get_class($this) . ':' . $this->_result->message );

        $this->_itemData = array();
        while( $o = $h->fetchObject( static::model_class ) ) {
            $o->deflate();
            $this->_itemData[] = $o;
        }
        return $this->_itemData;
    }





    /**
     * Implements Iterator methods 
     */
    public function rewind()
    { 
        $this->_itemCursor = 0;
    }

    /* is current row a valid row ? */
    public function valid()
    {
        if( $this->_itemData == null )
            $this->_readRows();
        return isset($this->_itemData[ $this->_itemCursor ] );
    }

    public function current() 
    { 
        return $this->_itemData[ $this->_itemCursor ];
    }

    public function next() 
    {
        return $this->_itemData[ $this->_itemCursor++ ];
    }

    public function key()
    {
        return $this->_itemCursor;
    }



    /** array access interface */

    public function offsetSet($name,$value)
    {
        if( null === $name ) {
            return $this->create($value);
        }
        $this->_items[ $name ] = $value;
    }

    public function offsetExists($name)
    {
        return isset($this->_items[ $name ]);
    }

    public function offsetGet($name)
    {
        if( isset( $this->_items[ $name ] ) )
            return $this->_items[ $name ];
    }

    public function offsetUnset($name)
    {
        unset($this->_items[$name]);
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

    public function toXml()
    {
        $list = $this->toArray();
        $xml = new \SerializerKit\XmlSerializer;
        return $xml->encode( $list );
    }


    public function toJson()
    {
        $list = $this->toArray();
        $json = new \SerializerKit\JsonSerializer;
        return $json->encode( $list );
    }

    public function toYaml()
    {
        $list = $this->toArray();
        $yaml = new \SerializerKit\YamlSerializer;
        return $yaml->encode( $list );
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



    public function create($args)
    {
        if( $this->_presetVars ) {
            $args = array_merge( $this->_presetVars , $args );
        }
        $model = $this->_schema->newModel();
        $return = $model->create($args);
        if( true === $return->success ) {
            if( $this->_postCreate ) {
                call_user_func( $this->_postCreate, $model, $args );
                // $this->_postCreate($model,$args);
            }
            return $model;
        }
        $this->_result = $return;
        return false;
    }




    public function setPostCreate($cb) 
    {
        $this->_postCreate = $cb;
    }

    public function setPresetVars($vars)
    {
        $this->_presetVars = $vars;
    }

    public function getLastSql()
    {
        return $this->_lastSql;
    }

    public function getVars()
    {
        return $this->_vars;
    }

    public function getResult()
    {
        return $this->result;
    }


    /**
     * Convert query to sql
     */
    public function toSql()
    {
        /* fetch by current query */
        $query = $this->_query;
        $sql = $query->build();
        $vars = $query->vars;
        foreach( $vars as $name => $value ) {
            $sql = str_replace( $name, $value, $sql );
        }
        return $sql;
    }


    /**
     * Override QueryBuilder->join method,
     * to enable explict selection.
     *
     * XXX: For model/collection objects, we should convert it to table name
     */
    public function join($table,$alias = null)
    {
        $this->setExplictSelect(true);
        return parent::join($table,$alias);
    }


}

