<?php
namespace LazyRecord;
use PDO;
use PDOException;
use RuntimeException;
use Exception;
use Iterator;
use ArrayAccess;
use Countable;

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
    implements Iterator, ArrayAccess, Countable, ExporterInterface
{
    public $schema;

    protected $_lastSql;

    protected $_vars;

    /**
     * @var SQLBuilder\QueryBuilder
     */
    public $_readQuery;

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

    protected $explictSelect = false;


    public function __construct() {
        // init a query
        $this->schema = SchemaLoader::load( static::schema_proxy_class );
    }

    public function getSchema() {
        return $this->schema;
    }

    public function __get( $key ) {
        /**
         * lazy attributes
         */
        if( $key === '_schema' ) {
            return SchemaLoader::load( static::schema_proxy_class );
        }
        elseif( $key === '_handle' ) {
            return $this->handle ?: $this->prepareData();
        }
        elseif( $key === '_query' ) {
            return $this->_readQuery 
                    ? $this->_readQuery
                    : $this->_readQuery = $this->createQuery( 
                        $this->_schema->getReadSourceId() 
                    );
        }
        elseif( $key === '_items' ) {
            return $this->_itemData ?: $this->_readRows();
        }
    }


    /**
     * Free cached row data and result handle, 
     * But still keep the same query
     *
     * @return $this
     */
    public function free()
    {
        $this->_itemData = null;
        $this->_result = null;
        $this->_itemCursor = null;
        $this->handle = null;
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
        $this->explictSelect = $boolean;
        return $this;
    }


    // TODO: maybe we should move this method into RuntimeSchema.
    // Because it's used in BaseModel class too
    public function getQueryDriver( $dsId )
    {
        return ConnectionManager::getInstance()->getQueryDriver( $dsId );
    }

    public function getWriteQueryDriver()
    {
        return $this->getQueryDriver( 
            $this->schema->getWriteSourceId()
        );
    }

    public function getReadQueryDriver()
    {
        return $this->getQueryDriver( 
            $this->schema->getReadSourceId()
        );
    }


    public function createQuery( $dsId )
    {
        $q = new QueryBuilder;
        $q->driver = $this->getQueryDriver( $dsId );
        $q->table( $this->schema->table );
        $q->select(
            $this->explictSelect 
                ? $this->getExplicitColumnSelect($q->driver)
                : '*'
        );
        $q->alias( $this->getAlias() ); // main table alias
        return $q;
    }


    // xxx: this might be used in other join statements.
    public function getExplicitColumnSelect($driver)
    {
        $alias = $this->getAlias();
        return array_map(function($name) use($alias,$driver) { 
                return $alias . '.' . $driver->getQuoteColumn( $name );
        }, $this->schema->getColumnNames());
    }

    /**
     * prepare data handle, call fetch method to read data from database, and 
     * catch the handle.
     *
     * Which calls doFetch() to do a query operation.
     */
    public function prepareData($force = false)
    {
        if( ! $this->handle || $force ) {
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
        $dsId = $this->schema->getReadSourceId();

        // XXX: here we use SQLBuilder\QueryBuilder to build our variables,
        //   but PDO doesnt accept boolean type value, we need to transform it.
        foreach( $vars as $k => & $v ) {
            if( $v === false )
                $v = 'FALSE';
            elseif( $v === true )
                $v = 'TRUE';
        }

        try {
            $this->handle = ConnectionManager::getInstance()->prepareAndExecute($dsId,$sql, $vars );
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
     * Clone current read query and apply select to count(*)
     * So that we can use the same conditions to query item count.
     *
     * @return int
     */
    public function queryCount()
    {
        $dsId = $this->schema->getReadSourceId();

        $q = clone $this->_query;
        $q->select( 'count(*)' ); // override current select.

        // when selecting count(*), we dont' use groupBys or order by
        $q->orders = array();
        return (int) ConnectionManager::getInstance()
                    ->prepareAndExecute($dsId,$q->build(),$q->vars)
                    ->fetchColumn();
    }


    /**
     * Get current selected item size 
     * by using php function `count`
     *
     * @return integer size
     */
    public function size()
    {
        return count($this->_items);
    }

    public function count() 
    {
        return $this->size();
    }


    /**
     * Query Limit for QueryBuilder
     *
     * @param integer $number
     */
    public function limit($number)
    {
        $this->_query->limit($number);
        return $this;
    }

    /**
     * Query offset for QueryBuilder
     *
     * @param integer $number 
     */
    public function offset($number)
    {
        $this->_query->offset($number);
        return $this;
    }



    /**
     * A Short helper method for using limit and offset of QueryBuilder.
     *
     * @param integer $page
     * @param integer $pageSize
     *
     * @return $this
     */
    public function page($page,$pageSize = 20)
    {
        $this->limit($pageSize);
        $this->offset(
            ($page - 1) * $pageSize
        );
        return $this;
    }

    /**
     * Get selected items and wrap it into a CollectionPager object.
     *
     * CollectionPager is a simple data pager, do not depends on database.
     *
     * @return LazyRecord\CollectionPager
     */
    public function pager($page = 1,$pageSize = 10)
    {
        // setup limit
        return new CollectionPager( $this->_items, $page, $pageSize );
    }

    /**
     * Get items
     *
     * @return LazyRecord\BaseModel[]
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

        if( ! $h ) {
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
        $items = $this->_items ?: array();
        return array_splice( $items, $pos, $count);
    }

    public function first()
    {
        return isset($this->_items[0]) ?
                $this->_items[0] : null;
    }

    public function last()
    {
        if( !empty($this->_items) ) {
            return end($this->_items);
        }
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
        $collection = new static;
        $collection->setRecords(
            array_map($cb,$this->_items)
        );
        return $collection;
    }

    public function filter($cb)
    {
        $collection = new static;
        $collection->setRecords(array_filter($this->_items,$cb));
        return $collection;
    }

    public function loadQuery( $sql, $args = array() , $dsId = null )
    {
        if( ! $dsId )
            $dsId = $this->schema->getReadSourceId();
        $this->handle = ConnectionManager::getInstance()->prepareAndExecute( $dsId, $sql , $args );
    }


    public function newModel()
    {
        return $this->schema->newModel();
    }


    static function fromArray($list)
    {
        $collection = new static;
        $schema = $collection->schema;
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
        $record = $this->schema->newModel();
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

    public function getSql() 
    {
        return $this->_lastSql;
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
        $sql   = $query->build();
        $vars  = $query->vars;
        foreach( $vars as $name => $value ) {
            $sql = str_replace( $name, $value, $sql );
        }
        return $sql;
    }


    /**
     * Override QueryBuilder->join method,
     * to enable explict selection.
     *
     * For model/collection objects, we should convert it to table name
     *
     *
     * Usage:
     *
     *       $collection->join( new Author, 'LEFT', 'a' ); // left join with alias 'a'
     *       $collection->join('authors'); // left join without alias
     *
     *       $collection->join( new Author, 'LEFT' , 'a' )
     *                  ->on('m.author_id', array('a.id') ); // LEFT JOIN authors table on m.author_id = a.id
     *
     *       $collection->join('authors','RIGHT','a'); // right join with alias 'a'
     *
     * @param mixed $target (Model object or table name)
     * @param string $type  Join Type (default 'LEFT')
     * @param string $alias Alias
     *
     * @return QueryBuilder
     */
    public function join($target, $type = 'LEFT' , $alias = null, $relationId = null )
    {
        $this->explictSelect = true;
        $query = $this->_query;

        // for models and schemas join
        if( is_object($target) ) {
            $table = $target->getTable();
            $columns = $target->getColumnNames();
            $select = array();
            $alias = $alias ?: $table;
            foreach( $columns as $name ) {
                $select[ $alias . '.' . $name ] = $alias . '_' . $name;
            }
            $query->addSelect($select);
            $expr = $query->join($table, $type); // it returns JoinExpression object

            if( $relationId ) {
                $relation = $this->schema->getRelation( $relationId );
                $expr->on()
                    ->equal( $this->getAlias() . '.' . $relation['self']['column'] , 
                    array(  ($alias ?: $table) . '.' . $relation['foreign']['column'] ));
            } else {
                $relations = $this->schema->relations;
                foreach( $relations as $relation ) {
                    if( ! isset($relation['foreign']) )
                        continue;

                    $fschema = new $relation['foreign']['schema'];
                    $modelClass = $fschema->getModelClass();

                    if( is_a($target, $fschema->getModelClass() ) ) {
                        $expr->on()
                            ->equal( $this->getAlias() . '.' . $relation['self']['column'] , 
                            array(  ($alias ?: $table) . '.' . $relation['foreign']['column'] ));
                        break;
                    }
                }
            }

            if( $alias ) {
                $expr->alias( $alias );
            }
            return $expr;
        }
        else {
            // For table name join
            $expr = $query->join($target, $type);
            if( $alias )
                $expr->alias($alias);
            return $expr;
        }
    }

    /**
     * Override QueryBuilder->where method,
     * to enable explict selection
     */
    public function where($args = null)
    {
        $this->setExplictSelect(true);
        if( $args && is_array($args) ) {
            return $this->_query->whereFromArgs($args);
        }
        return $this->_query->where();
    }


    public function add($record)
    {
        if( ! $this->_itemData )
            $this->_itemData = array();
        $this->_itemData[] = $record;
    }



    /**
     * Set record objects
     */
    public function setRecords($records)
    {
        $this->_itemData = $records;
    }



    /**
     * Free resources and reset query,arguments and data.
     *
     * @return $this
     */
    public function reset() 
    {
        $this->free();
        $this->_readQuery = null;
        $this->_vars = null;
        $this->_lastSQL = null;
        return $this;
    }



    /**
     * Return pair array by columns
     *
     * @param string $key
     * @param string $valueKey
     */
    public function asPairs($key,$valueKey)
    {
        $data = array();
        foreach( $this as $item ) {
            $data[ $item->get($key) ] = $item->get($valueKey);
        }
        return $data;
    }



    /**
     * When cloning collection object,
     * The resources will be free, and the 
     * query builder will be cloned.
     *
     */
    public function __clone() 
    {
        $this->free();
        if( $this->_readQuery ) {
            $this->_readQuery = clone $this->_readQuery;
        }
    }

    public function __toString() 
    {
        return $this->toSql();
    }
}

