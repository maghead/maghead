<?php
namespace LazyRecord;
use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;
use Exception;
use Iterator;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use BadMethodCallException;

use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\UpdateQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\ArgumentArray;

use LazyRecord\Result;
use LazyRecord\BaseModel;
use LazyRecord\ConnectionManager;
use LazyRecord\Schema\SchemaLoader;
use SerializerKit\YamlSerializer;
use SerializerKit\XmlSerializer;
use SerializerKit\JsonSerializer;

/**
 * base collection class
 */
class BaseCollection
    implements 
    ArrayAccess, 
    Countable, 
    IteratorAggregate
{
    protected $_lastSql;

    protected $_vars;

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
    protected $_rows = null;



    /**
     * preset vars for creating
     */
    protected $_presetVars = array();




    /**
     * @var array save joined alias and table names
     *
     *  Relationship Id => Joined Alias
     *
     */
    protected $_joinedRelationships = array();


    /**
     * postCreate hook
     */
    protected $_postCreate;


    protected $_schema;

    /**
     * operation result object
     */
    protected $_result;


    protected $_alias = 'm';

    protected $explictSelect = false;

    public $selected;

    /**
     * $this->defaultOrdering = array( 
     *    array( 'id', 'desc' ),
     *    array( 'name', 'desc' ),
     * );
     */
    protected $defaultOrdering = array();

    public function __construct() 
    {
    }
    

    public function getIterator()
    {
        if ( ! $this->_rows ) {
            $this->readRows();
        }
        return new ArrayIterator($this->_rows);
    }

    public function getSchema() 
    {
        if ($this->_schema){
            return $this->_schema;
        } elseif ( @constant('static::schema_proxy_class') ) {
            return $this->_schema = SchemaLoader::load( static::schema_proxy_class );
        } 
        throw new RuntimeException("schema is not defined in " . get_class($this) );
    }

    public function getCurrentReadQuery()
    {
        return $this->_readQuery ? $this->_readQuery : $this->_readQuery = $this->createReadQuery();
    }

    public function __get( $key ) {
        /**
         * lazy attributes
         */
        if ($key === '_handle' ) {
            return $this->handle ?: $this->prepareData();
        }
        throw new Exception("No such magic property $key");
    }

    public function getRows()
    {
        if ($this->_rows) {
            return $this->_rows;
        }
        $this->readRows();
        return $this->_rows;
    }

    /**
     * Free cached row data and result handle, 
     * But still keep the same query
     *
     * @return $this
     */
    public function free()
    {
        $this->_rows = null;
        $this->_result = null;
        $this->handle = null;
        return $this;
    }

    /**
     * Dispatch undefined methods to SelectQuery object,
     * To achieve mixin-like feature.
     */
    public function __call($m,$a)
    {
        $q = $this->getCurrentReadQuery();
        if (method_exists($q,$m) ) {
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
        if ($q = $this->getCurrentReadQuery()) {
            $q->alias($alias);
        }
        return $this;
    }

    public function setExplictSelect($boolean = true)
    {
        $this->explictSelect = $boolean;
        return $this;
    }

    public function select($sels) {
        $this->explictSelect = true;
        $this->selected = (array) $sels;
        return $this;
    }

    public function selectAll() {
        $dsId = $this->getSchema()->getReadSourceId();
        $driver = $this->getQueryDriver($dsId);
        $this->explictSelect = true;
        $this->selected = $this->getExplicitColumnSelect($driver);
        return $this;
    }

    public function getSelected() {
        if ($this->selected) {
            return $this->selected;
        }
    }


    // TODO: maybe we should move this method into RuntimeSchema.
    // Because it's used in BaseModel class too
    public function getQueryDriver($dsId)
    {
        return ConnectionManager::getInstance()->getQueryDriver( $dsId );
    }

    public function getWriteQueryDriver()
    {
        return $this->getQueryDriver($this->getSchema()->getWriteSourceId());
    }

    public function getReadQueryDriver()
    {
        return $this->getQueryDriver($this->getSchema()->getReadSourceId());
    }


    public function createReadQuery()
    {
        $dsId = $this->getSchema()->getReadSourceId();

        $conn = ConnectionManager::getInstance()->getConnection($dsId);
        $driver = $conn->createQueryDriver();

        $q = new SelectQuery;

        // Read from class consts
        $q->from($this->getSchema()->table, $this->getAlias()); // main table alias

        $selection = $this->getSelected();
        $q->select(
            $selection ? $selection
                : $this->explictSelect 
                    ? $this->getExplicitColumnSelect($driver)
                    : $this->getAlias() . '.*'
        );

        // Setup Default Ordering.
        if (! empty($this->defaultOrdering)) {
            foreach( $this->defaultOrdering as $ordering ) {
                $q->orderBy( $ordering[0], $ordering[1] );
            }
        }
        return $q;
    }


    // xxx: this might be used in other join statements.
    public function getExplicitColumnSelect(BaseDriver $driver)
    {
        $alias = $this->getAlias();
        return array_map(function($name) use($alias,$driver) { 
                return $alias . '.' . $driver->quoteIdentifier( $name );
        }, $this->getSchema()->getColumnNames());
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
     * Build sql and Fetch from current query, make a query to database.
     *
     * @return OperationResult
     */
    public function fetch()
    {
        /* fetch by current query */
        $dsId = $this->getSchema()->getReadSourceId();
        $conn = ConnectionManager::getInstance()->getConnection($dsId);
        $driver = $conn->createQueryDriver();

        $arguments = new ArgumentArray;

        $this->_lastSql = $sql = $this->getCurrentReadQuery()->toSql($driver, $arguments);
        $this->_vars = $vars = $arguments->toArray();

        try {
            $this->handle = $conn->prepareAndExecute($sql, $vars);
        } catch (Exception $e) {
            return Result::failure('Collection fetch failed: ' . $e->getMessage() , array( 
                'vars' => $vars,
                'sql' => $sql,
                'exception' => $e,
            ));
        }
        return Result::success('Updated', array( 'sql' => $sql ));
    }


    /**
     * Clone current read query and apply select to count(*)
     * So that we can use the same conditions to query item count.
     *
     * @return int
     */
    public function queryCount()
    {
        $dsId = $this->getSchema()->getReadSourceId();

        $conn = ConnectionManager::getInstance()
                    ->getConnection($dsId);

        $driver = $conn->createQueryDriver();

        $q = clone $this->getCurrentReadQuery();
        $q->setSelect('COUNT(distinct m.id)'); // Override current select.

        // when selecting count(*), we dont' use groupBys or order by
        $q->clearOrderBy();
        $q->clearGroupBy();

        $arguments = new ArgumentArray;
        $sql = $q->toSql($driver, $arguments);
        return (int) $conn->prepareAndExecute($sql, $arguments->toArray())
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
        if ($this->_rows) {
            return count($this->_rows);
        }
        $this->readRows();
        return count($this->_rows);
    }


    /**
     * This method implements the Countable interface
     */
    public function count() 
    {
        if ($this->_rows) {
            return count($this->_rows);
        }
        $this->readRows();
        return count($this->_rows);
    }


    /**
     * Query Limit for QueryBuilder
     *
     * @param integer $number
     */
    public function limit($number)
    {
        $this->getCurrentReadQuery()->limit($number);
        return $this;
    }

    /**
     * Query offset for QueryBuilder
     *
     * @param integer $number 
     */
    public function offset($number)
    {
        $this->getCurrentReadQuery()->offset($number);
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
        if (!$this->_rows) {
            $this->readRows();
        }
        // Setup limit
        return new CollectionPager($this->_rows, $page, $pageSize );
    }

    /**
     * Get items
     *
     * @return LazyRecord\BaseModel[]
     */
    public function items()
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        return $this->_rows;
    }

    public function fetchRow()
    {
        return $this->_handle->fetchObject( static::model_class );
    }



    /**
     * Read rows from database handle
     *
     * @return model_class[]
     */
    protected function readRows()
    {
        // initialize the connection handle object
        $h = $this->_handle;

        if ( ! $h ) {
            if ( $this->_result->exception ) {
                throw $this->_result->exception;
            }
            throw new RuntimeException( get_class($this) . ':' . $this->_result->message );
        }

        // Use fetch all
        return $this->_rows = $h->fetchAll(PDO::FETCH_CLASS, static::model_class );
    }


    public function delete()
    {
        $schema = $this->getSchema();
        $dsId = $schema->getWriteSourceId();

        $conn = ConnectionManager::getInstance()->getConnection($dsId);
        $driver = $conn->createQueryDriver();

        $query = new DeleteQuery;
        $query->from($schema->getTable());
        $query->setWhere(clone $this->getCurrentReadQuery()->getWhere());

        $arguments = new ArgumentArray;
        $sql = $query->toSql($driver, $arguments);

        try {
            $this->handle = $conn->prepareAndExecute($sql, $arguments->toArray());
        } catch (Exception $e) {
            return Result::failure('Collection delete failed: ' . $e->getMessage() , array( 
                'vars' => $arguments->toArray(),
                'sql' => $sql,
                'exception' => $e,
            ));
        }
        return Result::success('Deleted', array( 'sql' => $sql ));

    }


    /**
     * Update collection
     *
     * FIXME
     */
    public function update(array $data)
    {
        $schema = $this->getSchema();
        $dsId = $schema->getWriteSourceId();

        $conn = ConnectionManager::getInstance()->getConnection($dsId);
        $driver = $conn->createQueryDriver();

        $query = new UpdateQuery;
        $query->setWhere(clone $this->getCurrentReadQuery()->getWhere());
        $query->update($schema->getTable());
        $query->set($data);

        $arguments = new ArgumentArray;
        $sql = $query->toSql($driver, $arguments);

        try {
            $this->handle = $conn->prepareAndExecute($sql, $arguments->toArray());
        } catch (Exception $e) {
            return Result::failure('Collection update failed: ' . $e->getMessage() , array( 
                'vars' => $arguments->toArray(),
                'sql' => $sql,
                'exception' => $e,
            ));
        }
        return Result::success('Updated', array( 'sql' => $sql ));
    }

    public function splice($pos,$count = null)
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        return array_splice($this->_rows, $pos, $count);
    }

    public function first()
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        return ! empty($this->_rows) ? $this->_rows[0] : null;
    }

    public function last()
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        return end($this->_rows);
    }


    /** array access interface */
    public function offsetSet($name,$value)
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        if (NULL === $name ) {
            return $this->create($value);
        }
        $this->_rows[ $name ] = $value;
    }

    public function offsetExists($name)
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        return isset($this->_rows[ $name ]);
    }

    public function offsetGet($name)
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        if (isset( $this->_rows[ $name ] ) ) {
            return $this->_rows[ $name ];
        }
    }

    public function offsetUnset($name)
    {
        if (!$this->_rows) {
            $this->readRows();
        }
        unset($this->_rows[$name]);
    }

    public function each(callable $cb)
    {
        if (!$this->_rows) {
            $this->readRows();
        }

        $collection = new static;
        $collection->setRecords(
            array_map($cb,$this->_rows)
        );
        return $collection;
    }

    public function filter(callable $cb)
    {
        if (!$this->_rows) {
            $this->readRows();
        }

        $collection = new static;
        $collection->setRecords(array_filter($this->_rows,$cb));
        return $collection;
    }



    /**
     * Load Collection from a SQL query statement.
     *
     * @param string $sql
     * @param array $args
     * @param string $dsId
     */
    public function loadQuery($sql, array $args = array() , $dsId = null )
    {
        if ( ! $dsId ) {
            $dsId = $this->getSchema()->getReadSourceId();
        }
        $this->handle = ConnectionManager::getInstance()->getConnection($dsId)->prepareAndExecute($sql, $args);
    }




    /**
     * Create model object.
     *
     * @return object BaseModel
     */
    public function newModel()
    {
        return $this->getSchema()->newModel();
    }



    /**
     * Create a collection object from an data array.
     */
    static function fromArray(array $list)
    {
        $collection = new static;
        $schema = $collection->getSchema();
        $records = array();
        foreach( $list as $item ) {
            $model = $schema->newModel();
            $model->setStashedData($item);
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
        return json_encode($list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
    }

    public function toYaml()
    {
        $list = $this->toArray();
        $yaml = new YamlSerializer;
        return $yaml->encode( $list );
    }


    /**
     * Create new record or relationship record, 
     * and append the record into _rows list
     *
     * @param array $args Arguments for creating record
     *
     * @return mixed record object
     */
    public function create(array $args)
    {
        if( $this->_presetVars ) {
            $args = array_merge( $this->_presetVars , $args );
        }

        // model record
        $record = $this->getSchema()->newModel();
        $return = $record->create($args);
        if( $return->success ) {
            if( $this->_postCreate ) {
                $middleRecord = call_user_func( $this->_postCreate, $record, $args );
                // $this->_postCreate($record,$args);
            }
            $this->_rows[] = $record;
            return $record;
        }
        $this->_result = $return;
        return false;
    }




    public function setPostCreate(callable $cb) 
    {
        $this->_postCreate = $cb;
    }

    public function setPresetVars(array $vars)
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
        $query = $this->getCurrentReadQuery();
        $dsId = $this->getSchema()->getReadSourceId();
        $driver = $this->getQueryDriver($dsId);
        $arguments = new ArgumentArray;
        $sql   = $query->toSql($driver, $arguments);

        /*
         * FIXME:
        foreach($arguments as $name => $value) {
            $sql = preg_replace( "/$name\b/", $value, $sql );
        }
         */
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
        $query = $this->getCurrentReadQuery();

        // for models and schemas join
        if (is_object($target)) {
            $table = $target->getTable();


            /* XXX: should get selected column names by default, if not get all column names */
            $columns = $target->selected ?: $target->getColumnNames();

            if ( ! empty($columns) ) {
                $select = array();

                if ( $alias ) {
                    $target->setAlias($alias);
                }
                $alias = $target->getAlias() != 'm' ? $target->getAlias() : $table;

                foreach( $columns as $name ) {
                    // Select alias.column as alias_column
                    $select[ $alias . '.' . $name ] = $alias . '_' . $name;
                }
                $query->select($select);
            }
            $joinExpr = $query->join($table, $type); // it returns JoinExpression object

            // here the relationship is defined, join the it.
            if( $relationId ) {
                $relation = $this->getSchema()->getRelation( $relationId );
                $joinExpr->on()
                    ->equal( $this->getAlias() . '.' . $relation['self_column'] , 
                    array( $alias . '.' . $relation['foreign_column'] ));

                $this->_joinedRelationships[ $relationId ] = $alias;

            } else {
                // find the related relatinship from defined relatinpships
                $relations = $this->getSchema()->relations;
                foreach( $relations as $relationId => $relation ) {
                    if ( ! isset($relation['foreign_schema']) ) {
                        continue;
                    }

                    $fschema = new $relation['foreign_schema'];
                    if ( is_a($target, $fschema->getModelClass() ) ) {
                        $joinExpr->on()
                            ->equal( $this->getAlias() . '.' . $relation['self_column'] , 
                            array( $alias. '.' . $relation['foreign_column'] ));

                        $this->_joinedRelationships[ $relationId ] = $alias;
                        break;
                    }
                }
            }

            if ($alias) {
                $joinExpr->as($alias);
            }
            return $joinExpr;
        }
        else {
            // For table name join
            $joinExpr = $query->join($target, $type);
            if ($alias) {
                $joinExpr->as($alias);
            }
            return $joinExpr;
        }
    }

    /**
     * Override QueryBuilder->where method,
     * to enable explict selection
     */
    public function where(array $args = null)
    {
        $this->setExplictSelect(true);
        $query = $this->getCurrentReadQuery();
        if ($args && is_array($args)) {
            return $query->where($args);
        }
        return $query->where();
    }

    public function add(BaseModel $record)
    {
        $this->_rows[] = $record;
    }



    /**
     * Set record objects
     */
    public function setRecords(array $records)
    {
        $this->_rows = $records;
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

    public function toPairs($key,$valueKey) 
    {
        return $this->asPairs($key,$valueKey);
    }

    public function toLabelValuePairs() {
        $items = array();
        foreach( $this as $item ) {
            $items[] = array(
                "label" => $item->dataLabel(),
                "value" => $item->dataValue(),
            );
        }
        return $items;
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

        // if we have readQuery object, we should clone the query object 
        // for the new collection object.
        if( $this->_readQuery ) {
            $this->_readQuery = clone $this->_readQuery;
        }
    }

    public function __toString() 
    {
        return $this->toSql();
    }
}

