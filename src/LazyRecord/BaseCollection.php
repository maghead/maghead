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
use SerializerKit\YamlSerializer;
use SerializerKit\XmlSerializer;
use SerializerKit\JsonSerializer;

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
    protected $_readQuery;

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
        if( $key === '_schema' ) {
            return SchemaLoader::load( static::schema_proxy_class );
        }
        elseif( $key === '_connection' ) {
            return ConnectionManager::getInstance();
        }
        elseif( $key === '_handle' ) {
            return $this->handle ?: $this->prepareData();
        }
        elseif( $key === '_query' ) {
            return $this->_readQuery ?: $this->createQuery( $this->_schema->getReadSourceId() );
        }
        elseif( $key === '_items' ) {
            return $this->_itemData ?: $this->_readRows();
        }
    }


    /**
     * Free cached row data
     *
     * @return $this
     */
    public function free()
    {
        $this->_itemData = null;
        return $this;
    }


    /**
     * Dispatch undefined methods to QueryBuilder object,
     * To achieve mixin-like feature.
     */
    public function __call($m,$a)
    {
        $q = $this->_query;
        if( method_exists($q,$m) ) {
            return call_user_func_array(array($q,$m),$a);
        }
        throw new Exception("Undefined method $m");
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


    public function getQueryDriver( $dsId )
    {
        return $this->_connection->getQueryDriver( $dsId );
    }

    public function getWriteQueryDriver()
    {
        $id = $this->_schema->getWriteSourceId();
        return $this->getQueryDriver( $id );
    }

    public function getReadQueryDriver()
    {
        $id = $this->_schema->getReadSourceId();
        return $this->getQueryDriver( $id );
    }


    public function createQuery( $dsId )
    {
        $q = new QueryBuilder;
        $q->driver = $this->getQueryDriver( $dsId );
        $q->table( $this->_schema->table );
        $q->select(
            $this->_explictSelect 
                ? $this->getExplicitColumnSelect($q->driver)
                : '*'
        );
        $q->alias( $this->getAlias() ); // main table alias
        return $this->_readQuery = $q;
    }


    // xxx: this might be used in other join statements.
    public function getExplicitColumnSelect($driver)
    {
        $alias = $this->getAlias();
        return array_map(function($name) use($alias,$driver) { 
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

        $dsId = $this->_schema->getReadSourceId();

        // XXX: here we use SQLBuilder\QueryBuilder to build our variables,
        //   but PDO doesnt accept boolean type value, we need to transform it.
        foreach( $vars as $k => & $v ) {
            if( $v === false )
                $v = 'FALSE';
            elseif( $v === true )
                $v = 'TRUE';
        }

        try {
            $this->handle = $this->_connection->prepareAndExecute($dsId,$sql, $vars );
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
     * Select count(*) statement to current query
     */
    public function queryCount()
    {
        $dsId = $this->_schema->getReadSourceId();
        $query = $this->_query;
        $query->select( 'count(*)' );
        // XXX: hack
        $query->groupBys = array();
        $query->orders = array();

        $sql  = $query->build();
        $vars = $query->vars;
        $handle = $this->_connection->prepareAndExecute($dsId,$sql, $vars);
        return (int) $handle->fetchColumn();
    }


    /**
     * Get selected item size.
     *
     * @return integer size
     */
    public function size()
    {
        return count($this->_items);
    }

    public function limit($number)
    {
        $this->_query->limit($number);
        return $this;
    }

    public function offset($number)
    {
        $this->_query->offset($number);
        return $this;
    }

    public function page($page,$pageSize = 20)
    {
        $this->limit($pageSize);
        $this->offset(
            ($page - 1) * $pageSize
        );
        return $this;
    }

    /**
     * Get selected items and wrap it into a CollectionPager object
     *
     * CollectionPager is a simple data pager, do not depends on database.
     *
     * @return CollectionPager
     */
    public function pager($page = 1,$pageSize = 10)
    {
        // setup limit
        return new CollectionPager( $this->_items, $page, $pageSize );
    }


    /**
     * Get items
     */
    public function items()
    {
        return $this->_items;
    }

    protected function _fetchRow()
    {
        return $this->_handle->fetchObject( static::model_class );
    }

    /**
     * Read rows from database handle
     *
     * @return model_class[]
     */
    protected function _readRows()
    {
        $h = $this->_handle;

        if( $h === null ) {
            if( $this->_result->exception )
                throw $this->_result->exception;
            throw new RuntimeException( get_class($this) . ':' . $this->_result->message );
        }

        // XXX: should be lazy
        $this->_itemData = array();
        while( $o = $this->_fetchRow() ) {
            $this->_itemData[] = $o;
        }
        return $this->_itemData;
    }





    /******************** Implements Iterator methods ********************/
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

    /*********************** End of Iterator methods ************************/



    public function splice($pos,$count = null)
    {
        if( $this->_items )
            return array_splice( $this->_items, $pos, $count);
        return array();
    }

    public function first()
    {
        return isset($this->_items[0]) ?
                $this->_items[0] : null;
    }

    public function last()
    {
        if( $this->_items )
            return end($this->_items);
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

    public function each($cb)
    {
        $items = array_map($cb,$this->_items);
        $collection = new static;
        $collection->setRecords($items);
        return $collection;
    }

    public function filter($cb)
    {
        $items = array_filter($this->_items,$cb);
        $collection = new static;
        $collection->setRecords($items);
        return $collection;
    }

    public function loadQuery( $sql, $args = array() , $dsId = null )
    {
        if( ! $dsId )
            $dsId = $this->_schema->getReadSourceId();
        $stm = $this->_connection->prepareAndExecute( $dsId, $sql , $args );
        $this->handle = $stm;
    }


    static function fromArray($list)
    {
        $collection = new static;
        $schema = $collection->_schema;
        $records = array();
        foreach( $list as $item ) {
            $model = $schema->newModel();
            $model->setData($item);
            $records[] = $model;
        }
        $collection->setRecords( $records );
        return $collection;
    }


    public function toArray()
    {
        return array_map( function($item) { 
                            return $item->toArray();
                        } , array_filter($this->items(), function($item) {
                                return $item->currentUserCan( $item->getCurrentUser() , 'read' );
                            }));
    }

    public function toXml()
    {
        $list = $this->toArray();
        $xml = new XmlSerializer;
        return $xml->encode( $list );
    }


    public function toJson()
    {
        $list = $this->toArray();
        $json = new JsonSerializer;
        return $json->encode( $list );
    }

    public function toYaml()
    {
        $list = $this->toArray();
        $yaml = new YamlSerializer;
        return $yaml->encode( $list );
    }


    /**
     * Create new record or relationship record, 
     * and append the record into _itemData list
     *
     * @param array $args Arguments for creating record
     *
     * @return mixed record object
     */
    public function create($args)
    {
        if( $this->_presetVars ) {
            $args = array_merge( $this->_presetVars , $args );
        }

        // model record
        $record = $this->_schema->newModel();
        $return = $record->create($args);
        if( $return->success ) {
            if( $this->_postCreate ) {
                $middleRecord = call_user_func( $this->_postCreate, $record, $args );
                // $this->_postCreate($record,$args);
            }
            $this->_itemData[] = $record;
            return $record;
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
        return $this->_result;
    }


    /**
     * Convert query to plain sql.
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
        return $this->_query->join($table,$alias);
    }

    /**
     * Override QueryBuilder->where method,
     * to enable explict selection
     */
    public function where()
    {
        $this->setExplictSelect(true);
        return $this->_query->where();
    }


    public function add($record)
    {
        if( ! $this->_itemData )
            $this->_itemData = array();
        $this->_itemData[] = $record;
    }

    public function setRecords($records)
    {
        $this->_itemData = $records;
    }



    /**
     * Return pair array by columns
     *
     * @param string $key
     * @param string $valueKey
     */
    public function asPair($key,$valueKey)
    {
        $data = array();
        foreach( $this as $item ) {
            $keyValue = $item->get($key);
            $value    = $item->get($valueKey);
            $data[ $keyValue ] = $value;
        }
        return $data;
    }
}

