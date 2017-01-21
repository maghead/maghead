<?php

namespace LazyRecord;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use BadMethodCallException;
use PDO;
use PDOException;
use ArrayIterator;
use Serializable;
use ArrayAccess;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\UpdateQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Universal\Query\InsertQuery;
use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Raw;
use LazyRecord\Result\OperationError;
use LazyRecord\Schema\SchemaLoader;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship\Relationship;
use LazyRecord\Exception\MissingPrimaryKeyException;
use LazyRecord\Exception\QueryException;
use LazyRecord\Connection;
use SerializerKit\XmlSerializer;
use ActionKit;
use Symfony\Component\Yaml\Yaml;

defined('YAML_UTF8_ENCODING') || define('YAML_UTF8_ENCODING', 0);

/**
 * Base Model class,
 * every model class extends from this class.
 */
abstract class BaseModel implements Serializable
{
    public static $yamlExtension;
    public static $yamlEncoding = YAML_UTF8_ENCODING;

    const SCHEMA_PROXY_CLASS = '';

    protected $_data = array();

    protected $_cache = array();

    protected $_foreignRecordCache = array();

    /**
     * @var bool Auto reload record after creating new record
     *
     * Turn off this if you want performance.
     */
    public $autoReload = false;

    public $dataLabelField;

    public $dataValueField;

    // static $schemaCache;

    public $usingDataSource;

    public $alias;

    public $selected;

    protected $_schema;

    public static $_cacheInstance;

    /**
     * @var array Mixin classes are emtpy. (MixinDeclareSchema)
     * */
    public static $mixin_classes = array();

    /**
     * @var PDOStatement prepared statement for find by primary key method.
     */
    protected $_preparedCreateStms = array();

    private $_readConnection;

    private $_writeConnection;

    public function select($sels)
    {
        if (is_array($sels)) {
            $this->selected = $sels;
        } else {
            $this->selected = func_get_args();
        }

        return $this;
    }

    public function getSelected()
    {
        return $this->selected;
    }

    protected function getCachePrefix()
    {
        return static::SCHEMA_CLASS;
    }

    public function unsetPrimaryKey()
    {
        $this->setKey(NULL);
    }

    /**
     * Use specific data source for data operations.
     *
     * @param string $dsId data source id.
     */
    public function using($dsId)
    {
        $this->readSourceId = $dsId;
        $this->writeSourceId = $dsId;

        return $this;
    }

    public function getDataLabelField()
    {
        return $this->dataLabelField ?: static::PRIMARY_KEY;
    }

    public function getDataValueField()
    {
        return $this->dataValueField ?: static::PRIMARY_KEY;
    }

    /**
     * This is for select widget,
     * returns label value from specific column.
     */
    public function dataLabel()
    {
        return $this->get($this->getDataLabelField());
    }

    /**
     * This is for select widget,
     * returns data key from specific column.
     */
    public function dataKeyValue()
    {
        return $this->get($this->getDataValueField());
    }

    /**
     * Alias method of $this->dataValue().
     */
    public function dataValue()
    {
        return $this->dataKeyValue();
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Trigger method for "before creating new record".
     *
     * By overriding this method, you can modify the 
     * arguments that is passed to the query builder.
     *
     * Remember to return the arguments back.
     *
     * @param array $args Arguments
     *
     * @return array $args Arguments
     */
    public function beforeCreate($args)
    {
        return $args;
    }

    /**
     * Trigger for after creating new record.
     *
     * @param array $args
     */
    public function afterCreate($args)
    {
    }

    /**
     * Trigger method for.
     */
    public function beforeDelete()
    {
    }

    public function afterDelete()
    {
    }

    public function beforeUpdate($args)
    {
        return $args;
    }

    public function afterUpdate($args)
    {
    }

    /**
     * To support static operation methods like ::create, ::update, we 
     * can not define methods with the same name, so that 
     * we dispatch these methods from the magic method __call.
     *
     * __call method is slower than normal method, because there are
     * one more method table to look up. you should call `create` method
     * if you need a better performance.
     */
    public function __call($m, $a)
    {
        switch ($m) {
        case 'update':
        case 'load':
        case 'delete':
            return call_user_func_array(array($this, '_'.$m), $a);
            break;
            // XXX: can dispatch methods to Schema object.
            // return call_user_func_array( array(  ) )
            break;
        }

        // Dispatch to schema object method first
        $schema = static::getSchema();
        if (method_exists($schema, $m)) {
            return call_user_func_array(array($schema, $m), $a);
        }

        // then it's the mixin methods
        if ($mClass = $this->findMixinMethodClass($m)) {
            return $this->invokeMixinClassMethod($mClass, $m, $a);
        }

        // XXX: special case for twig template
        throw new BadMethodCallException(get_class($this).": $m method not found.");
    }

    /**
     * Find methods in mixin schema classes, methods will be called statically.
     *
     * @param string $m method name
     *
     * @return string the mixin class name.
     */
    public function findMixinMethodClass($m)
    {
        foreach (static::$mixin_classes as $mixinClass) {
            // if we found it, just call it and return the result. 
            if (method_exists($mixinClass, $m)) {
                return $mixinClass;
            }
        }

        return false;
    }

    /**
     * Invoke method on all mixin classes statically. this method does not return anything.
     *
     * @param string $m method name.
     * @param array  $a method arguments.
     */
    protected function invokeAllMixinMethods($m, $a)
    {
        foreach (static::$mixin_classes as $mixinClass) {
            // if we found it, just call it and return the result. 
            if (method_exists($mixinClass, $m)) {
                call_user_func_array(array($mixinClass, $m), array_merge(array($this), $a));
            }
        }
    }


    /**
     * An alias for BaseRepo::loadByKeys
     */
    static protected function loadByKeys(array $args, $byKeys = null)
    {
        return static::defaultRepo()->loadByKeys($args, $byKeys);
    }

    /**
     * Create or update an record by checking 
     * the existence from the $byKeys array 
     * that you defined.
     *
     * If the record exists, then the record should be updated.
     * If the record does not exist, then the record should be created.
     *
     * @param array $byKeys
     * @return Result
     */
    public function updateOrCreate(array $args, $byKeys = null)
    {
        $record = static::loadByKeys($args, $byKeys);
        if ($record) {
            $this->update($args);
            return $record;
        } else {
            return $this->create($args);
        }
        throw new MissingPrimaryKeyException('primary key is not defined.');
    }


    /**
     * Create a record if the record does not exists
     * Otherwise the record should be updated with the arguments.
     *
     * @param array $args
     * @param array $byKeys it's optional if you defined primary key
     */
    public function loadOrCreate(array $args, $byKeys = null)
    {
        $record = static::loadByKeys($args, $byKeys);
        if ($record) {
            return $record;
        }
        $ret = $this->create($args);
        if ($ret->error) {
            return false;
        }
        return static::find($ret->key);
    }



    /**
     * Get the RuntimeColumn objects from RuntimeSchema object.
     */
    public function columns()
    {
        return static::getSchema()->columns;
    }

    static public function create(array $args)
    {
        return static::defaultRepo()->create($args);
    }

    /**
     * Create and return the created record.
     */
    static public function createAndLoad(array $args)
    {
        $repo = static::defaultRepo();
        $ret = $repo->create($args);
        if ($ret->error) {
            return false;
        }
        return $repo->find($ret->key);
    }

    static public function _validateColumn($c, $val, $args, $record)
    {
        return static::defaultRepo()::_validateColumn($c, $val, $args, $record);
    }

    public function setPreferredTable($tableName)
    {
        $this->table = $tableName;
    }

    /**
     * We kept getTable() as dynamic that way we can change the table name.
     */
    public function getTable()
    {
        return $this->table ?: static::TABLE;
    }

    public function getAlias()
    {
        return $this->alias ?: static::TABLE_ALIAS;
    }

    /**
     * find() is an alias method of defaultRepo->find
     */
    static public function find($pkId)
    {
        return static::defaultRepo()->find($pkId);
    }

    static public function load($args)
    {
        return static::defaultRepo()->load($args);
    }

    static public function loadForUpdate($args)
    {
        return static::defaultRepo()->loadForUpdate($args);
    }

    static protected function _stmFetch($stm, $args)
    {
        $stm->execute($args);
        $obj = $stm->fetch(PDO::FETCH_CLASS);
        $stm->closeCursor();
        return $obj;
    }


    /**
     * Delete current record, the record should be loaded already.
     *
     * @return Result operation result (success or error)
     */
    public function delete()
    {
        $key = $this->getKey();
        $write = $this->getWriteConnection();
        $this->beforeDelete();
        static::createRepo($write, $write)->deleteByPrimaryKey($key);
        $this->afterDelete();
        return Result::success('Record deleted', [ 'type' => Result::TYPE_DELETE ]);
    }

    /**
     * Update current record.
     *
     * @param array $args
     *
     * @return Result operation result (success or error)
     */
    public function update(array $args, $options = array())
    {
        // check if the record is loaded.
        $k = static::PRIMARY_KEY;

        // check if we get primary key value
        // here we allow users to specifty primary key value from arguments if the record is not loaded.
        $kVal = null;
        if (isset($args[$k]) && is_scalar($args[$k])) {
            $kVal = $args[$k];
            unset($args[$k]);
        } else if ($k = $this->getKey()) {
            $kVal = $k;
        }
        if (!$kVal) {
            return Result::failure('Record is not loaded, Can not update record.', array('args' => $args));
        }
        $ret = static::defaultRepo()->updateByPrimaryKey($kVal, $args);
        $this->setData($args);
        return $ret;
    }

    /**
     * Simply update record without validation and triggers.
     *
     * @param array $args
     */
    public function rawUpdate(array $args)
    {
        $conn = $this->getWriteConnection();
        $driver = $conn->getQueryDriver();
        $k = static::PRIMARY_KEY;
        $kVal = isset($args[$k])
            ? $args[$k] 
            : $this->getKey();

        $arguments = new ArgumentArray();
        $query = new UpdateQuery();
        $query->set($args);
        $query->update($this->table);
        $query->where()->equal($k, $kVal);

        $sql = $query->toSql($driver, $arguments);

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());
        $this->setData($args);
        return Result::success('Update success', array(
            'sql' => $sql,
            'type' => Result::TYPE_UPDATE,
        ));
    }

    /**
     * Simply create record without validation and triggers.
     *
     * @param array $args
     */
    static public function rawCreate(array $args)
    {
        return static::defaultRepo()->rawCreate($args);
    }

    /**
     * Save current data (create or update)
     * if primary key is defined, do update
     * if primary key is not defined, do create.
     *
     * @return Result operation result (success or error)
     */
    public function save()
    {
        $key = $this->getKey();
        $data = $this->getData();
        if ($key) {
            return static::defaultRepo()->updateByPrimaryKey($key, $data);
        }
        return static::defaultRepo()->create($data);
    }







    /**
     * Invoke single mixin class method statically,.
     *
     * @param string $mixinClass mixin class name.
     * @param string $m          method name.
     * @parma array  $a method arguments.
     *
     * @return mixed execution result
     */
    protected function invokeMixinClassMethod($mixinClass, $m, array $a)
    {
        return call_user_func_array(array($mixinClass, $m), array_merge(array($this), $a));
    }



    /**
     * Render readable column value.
     *
     * @param string $name column name
     */
    public function display($name)
    {
        if ($c = static::getSchema()->getColumn($name)) {
            // get raw value
            if ($c->virtual) {
                return $this->get($name);
            }
            return $c->display($this->getValue($name));
        } elseif (isset($this->$name)) {
            return $this->$name;
        }

        // for relationship record
        $val = $this->get($name);
        if ($val && $val instanceof \LazyRecord\BaseModel) {
            return $val->dataLabel();
        }
    }


    /**
     * get pdo connetion and make a query.
     *
     * @param string $sql SQL statement
     *
     * @return PDOStatement pdo statement object.
     *
     *     $stm = $this->dbQuery($sql);
     *     foreach( $stm as $row ) {
     *              $row['name'];
     *     }
     */
    public function dbQuery($dsId, $sql)
    {
        $conn = $this->getConnection($dsId);
        if (!$conn) {
            throw new RuntimeException("data source $dsId is not defined.");
        }

        return $conn->query($sql);
    }

    /**
     * Load record from an sql query.
     *
     * @param string $sql  sql statement
     * @param array  $args
     * @param string $dsId data source id
     *
     *     $result = $record->loadQuery( 'select * from ....', array( ... ) , 'master' );
     *
     * @return Result
     */
    public function loadQuery($sql, array $args = array(), $dsId = null)
    {
        if (!$dsId) {
            $dsId = $this->readSourceId;
        }

        $conn = $this->getConnection($dsId);
        $stm = $conn->prepare($sql);
        $stm->setFetchMode(PDO::FETCH_CLASS, get_class($this));
        $stm->execute($args);
        if (false === ($data = $stm->fetch(PDO::FETCH_CLASS))) {
            return Result::failure('Data load failed.', array(
                'sql'  => $sql,
                'args' => $args,
            ));
        }
        $this->setData($data);
        return Result::success('Data loaded', array(
            'key'  => $this->getKey(),
            'sql' => $sql,
        ));
    }

    /**
     * We should move this method into connection manager.
     *
     * @return PDOStatement
     */
    public function dbPrepareAndExecute(PDO $conn, $sql, array $args = array())
    {
        $stm = $conn->prepare($sql);
        $stm->execute($args);

        return $stm;
    }

    /**
     * get default connection object (PDO) from connection manager.
     *
     * @param string $dsId data source id
     *
     * @return PDO
     */
    public function getConnection($dsId = 'default')
    {
        $connManager = ConnectionManager::getInstance();

        return $connManager->getConnection($dsId);
    }

    /**
     * Get PDO connection for writing data.
     *
     * @return PDO
     */
    public function getWriteConnection()
    {
        return $this->_writeConnection
            ? $this->_writeConnection
            : $this->_writeConnection = ConnectionManager::getInstance()->getConnection($this->writeSourceId);
    }

    /**
     * Get PDO connection for reading data.
     *
     * @return PDO
     */
    public function getReadConnection()
    {
        return $this->_readConnection
            ? $this->_readConnection
            : $this->_readConnection = ConnectionManager::getInstance()->getConnection($this->readSourceId);
    }

    public function getSchemaProxyClass()
    {
        return static::SCHEMA_PROXY_CLASS;
    }

    /**
     * Get inflate value.
     *
     * @param string $name Column name
     */
    public function get($key)
    {
        // relationship id can override value column.
        if ($relation = static::getSchema()->getRelation($key)) {
            // use model query to load relational record.
            return $this->getRelationalRecords($key, $relation);
        }
        return $this->inflateColumnValue($key);
    }

    /**
     * Check if the value exist.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasValue($name)
    {
        return isset($this->$name);
    }

    /**
     * Get the raw value from record (without deflator).
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getValue($name)
    {
        return $this->$name;
    }

    abstract public function getData();

    abstract public function setData(array $data);

    /**
     * Do we have this column ?
     *
     * @param string $name
     */
    public function __isset($name)
    {
        return property_exists($this, $name)
            || isset(static::getSchema()->columns[$name])
            || 'schema' === $name
            || $this->getSchema()->getRelation($name)
            ;
    }

    public function getRelationalRecords($key, $relation = null)
    {
        // check for the object cache
        $cacheKey = 'relationship::'.$key;
        if (!$relation) {
            $relation = static::getSchema()->getRelation($key);
        }

        /*
        switch($relation['type']) {
            case Relationship::HAS_ONE:
            case Relationship::HAS_MANY:
            break;
        }
        */
        if (Relationship::HAS_ONE === $relation['type']) {
            $sColumn = $relation['self_column'];

            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];
            if (!$this->$sColumn) {
                return;
            }

            // throw new Exception("The value of $sColumn of " . get_class($this) . ' is not defined.');
            $sValue = $this->$sColumn;

            $model = $relation->newForeignModel();
            $record = $model::load(array($fColumn => $sValue));
            return $this->setInternalCache($cacheKey, $record);
        } elseif (Relationship::HAS_MANY === $relation['type']) {

            
            // TODO: migrate this code to Relationship class.
            $sColumn = $relation['self_column'];
            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];

            if (!isset($this->$sColumn)) {
                return;
            }
            // throw new Exception("The value of $sColumn of " . get_class($this) . ' is not defined.');

            $sValue = $this->$sColumn;

            $collection = $relation->getForeignCollection();
            $collection->where()
                ->equal($collection->getAlias().'.'.$fColumn, $sValue); // where 'm' is the default alias.

            // For if we need to create relational records 
            // though collection object, we need to pre-set 
            // the relational record id.
            $collection->setPresetVars(array($fColumn => $sValue));

            return $this->setInternalCache($cacheKey, $collection);
        }
        // belongs to one record
        elseif (Relationship::BELONGS_TO === $relation['type']) {
            $sColumn = $relation['self_column'];
            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];
            $fpSchema = SchemaLoader::load($fSchema->getSchemaProxyClass());

            if (!isset($this->$sColumn)) {
                return;
            }

            $sValue = $this->$sColumn;
            $model = $fpSchema->newModel();
            $record = $model::load(array($fColumn => $sValue));
            return $this->setInternalCache($cacheKey, $record);

        } elseif (Relationship::MANY_TO_MANY === $relation['type']) {
            $rId = $relation['relation_junction'];  // use relationId to get middle relation. (author_books)
            $rId2 = $relation['relation_foreign'];  // get external relationId from the middle relation. (book from author_books)

            $middleRelation = static::getSchema()->getRelation($rId);
            if (!$middleRelation) {
                throw new InvalidArgumentException("first level relationship of many-to-many $rId is empty");
            }

            // eg. author_books
            $sColumn = $middleRelation['foreign_column'];
            $sSchema = $middleRelation->newForeignSchema();
            $spSchema = SchemaLoader::load($sSchema->getSchemaProxyClass());

            $foreignRelation = $spSchema->getRelation($rId2);
            if (!$foreignRelation) {
                throw new InvalidArgumentException("second level relationship of many-to-many $rId2 is empty.");
            }

            $fSchema = $foreignRelation->newForeignSchema();
            $fColumn = $foreignRelation['foreign_column'];
            $fpSchema = SchemaLoader::load($fSchema->getSchemaProxyClass());

            $collection = $fpSchema->newCollection();

            /*
                * join middle relation ship
                *
                *    Select * from books b (r2) left join author_books ab on ( ab.book_id = b.id )
                *       where b.author_id = :author_id
                */
            $collection->join($sSchema->getTable())->as('b')
                            ->on()
                            ->equal('b.'.$foreignRelation['self_column'], array($collection->getAlias().'.'.$fColumn));

            $value = $this->getValue($middleRelation['self_column']);
            $collection->where()
                ->equal(
                    'b.'.$middleRelation['foreign_column'],
                    $value
                );

            /*
                * for many-to-many creation:
                *
                *    $author->books[] = array(
                *        ':author_books' => array( 'created_on' => date('c') ),
                *        'title' => 'Book Title',
                *    );
                */
            $collection->setPostCreate(function ($record, $args) use ($spSchema, $rId, $middleRelation, $foreignRelation, $value) {
                // arguments for creating middle-relationship record
                $a = array(
                    $foreignRelation['self_column'] => $record->getValue($foreignRelation['foreign_column']),  // 2nd relation model id
                    $middleRelation['foreign_column'] => $value,  // self id
                );

                if (isset($args[':'.$rId ])) {
                    $a = array_merge($args[':'.$rId ], $a);
                }

                // create relationship
                $middleRecord = $spSchema->newModel();
                $ret = $middleRecord->create($a);
                if ($ret->error) {
                    throw new Exception("$rId create failed.");
                }

                return $middleRecord;
            });

            return $this->setInternalCache($cacheKey, $collection);
        }

        throw new Exception("The relationship type of $key is not supported.");
    }

    /**
     * __get magic method is used for getting:
     *
     * 1. virtual column data
     * 2. relational records
     *
     * @param string $key
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Return the collection object of current model object.
     *
     * @return LazyRecord\BaseCollection
     */
    public function asCollection()
    {
        $class = static::COLLECTION_CLASS;
        return new $class();
    }

    /**
     * return data stash array,.
     *
     * @return array
     */
    public function toArray(array $fields = null)
    {
        $data = $this->getData();
        if ($fields) {
            return array_intersect_key($data, array_flip($fields));
        }
        return $data;
    }

    /**
     * return json format data.
     *
     * @return string JSON string
     */
    public function toJson()
    {
        $data = $this->getData();
        return json_encode($data);
    }

    /**
     * Return xml format data.
     *
     * @return string XML string
     */
    public function toXml()
    {
        // TODO: improve element attributes
        $ser = new XmlSerializer();
        $data = $this->getData();
        return $ser->encode($data);
    }

    /**
     * Return YAML format data.
     *
     * @return string YAML string
     */
    public function toYaml()
    {
        $data = $this->getData();
        self::$yamlExtension = extension_loaded('yaml');
        if (self::$yamlExtension) {
            return yaml_emit($data, YAML_UTF8_ENCODING);
        }
        return file_put_contents($yamlFile, "---\n".Yaml::dump($data, $inline = true, $exceptionOnInvalidType = true));
    }

    /**
     * Deflate data and return.
     *
     * @return array
     */
    public function toInflatedArray()
    {
        $data = array();
        $schema = static::getSchema();
        $data = $this->getData();
        foreach ($data as $k => $v) {
            $col = $schema->getColumn($k);
            if ($col && $col->isa) {
                $data[ $k ] = $col->inflate($v, $this);
            } else {
                $data[ $k ] = $v;
            }
        }

        return $data;
    }

    /**
     * Inflate column value.
     *
     * @param string $n Column name
     *
     * @return mixed
     */
    protected function inflateColumnValue($n)
    {
        $value = property_exists($this, $n) ? $this->$n : NULL;
        if ($c = static::getSchema()->getColumn($n)) {
            return $c->inflate($value, $this);
        }
        return $value;
    }

    public function getDeclareSchema()
    {
        $class = static::SCHEMA_CLASS;

        return new $class();
    }

    static public function getSchema()
    {
        if ($this->_schema) {
            return $this->_schema;
        } elseif (@constant('static::SCHEMA_PROXY_CLASS')) {
            // the SCHEMA_PROXY_CLASS is from the *Base.php file.
            if ($this->_schema = SchemaLoader::load(static::SCHEMA_PROXY_CLASS)) {
                return $this->_schema;
            }
            throw new Exception('Can not load '.static::SCHEMA_PROXY_CLASS);
        }
        throw new RuntimeException('schema is not defined in '.get_class($this));
    }

    /***************************************
     * Cache related methods
     ***************************************/

    /**
     * flush internal cache, in php memory.
     */
    public function flushCache()
    {
        $this->_cache = array();
    }

    /**
     * set internal cache, in php memory.
     *
     * @param string $key cache key
     * @param mixed  $val cache value
     *
     * @return mixed cached value
     */
    public function setInternalCache($key, $val)
    {
        return $this->_cache[ $key ] = $val;
    }

    /**
     * get internal cache from php memory.
     *
     * @param string $key cache key
     *
     * @return mixed cached value
     */
    public function getInternalCache($key)
    {
        if (isset($this->_cache[ $key ])) {
            return $this->_cache[ $key ];
        }
    }

    public function hasInternalCache($key)
    {
        return isset($this->_cache[ $key ]);
    }

    public function clearInternalCache()
    {
        $this->_cache = array();
    }

    public static function getCacheInstance()
    {
        if (self::$_cacheInstance) {
            return self::$_cacheInstance;
        }

        return self::$_cacheInstance = ConfigLoader::getInstance()->getCacheInstance();
    }

    private function getCache($key)
    {
        if ($cache = self::getCacheInstance()) {
            return $cache->get($this->getCachePrefix().$key);
        }
    }

    private function setCache($key, $val, $ttl = 0)
    {
        if ($cache = self::getCacheInstance()) {
            $cache->set($this->getCachePrefix().$key, $val, $ttl);
        }

        return $val;
    }

    public function getWriteSourceId()
    {
        return $this->writeSourceId;
    }

    public function getReadSourceId()
    {
        return $this->readSourceId;
    }

    public function fetchOneToManyRelationCollection($relationId)
    {
        if ($this->id && isset($this->{ $relationId })) {
            return $this->{$relationId};
        }
    }

    public function fetchManyToManyRelationCollection($relationId)
    {
        $schema = static::getSchema();
        $relation = $schema->getRelation($relationId);

        return $relation->newForeignForeignCollection(
            $schema->getRelation($relation['relation_junction'])
        );
    }

    public function asCreateAction(array $args = array(), array $options = array())
    {
        // the create action requires empty args
        return $this->newAction('Create', $args, $options);
    }

    public function asUpdateAction(array $args = array(), array $options = array())
    {
        // should only update the defined fields
        return $this->newAction('Update', $args, $options);
    }

    public function asDeleteAction(array $args = array(), array $options = array())
    {
        $pk = static::PRIMARY_KEY;
        if ($this->hasKey()) {
            $args[$pk] = $this->getKey();
        }
        $data = $this->getData();
        return $this->newAction('Delete', array_merge($data, $args), $options);
    }

    /**
     * Create an action from existing record object.
     *
     * @param string $type 'create','update','delete'
     */
    public function newAction($type, array $args = array(), $options = array())
    {
        $class = get_class($this);
        $actionClass = \ActionKit\RecordAction\BaseRecordAction::createCRUDClass($class, $type);
        $options['record'] = $this;

        return new $actionClass($args, $options);
    }

    public function getRecordActionClass($type)
    {
        $class = get_class($this);
        return \ActionKit\RecordAction\BaseRecordAction::createCRUDClass($class, $type);
    }

    // =========================== REPO METHODS ===========================

    /**
     * defaultRepo method creates the Repo instance class with the default data source IDs
     *
     * @return BaseRepo
     */
    static public function defaultRepo()
    {
        $connManager = ConnectionManager::getInstance();
        $write = $connManager->getConnection(static::WRITE_SOURCE_ID);
        $read  = $connManager->getConnection(static::READ_SOURCE_ID);
        return static::createRepo($write, $read);
    }

    static public function repo($write = null, $read = null)
    {
        $connManager = ConnectionManager::getInstance();
        if (!$read) {
            if (!$write) {
                return static::defaultRepo();
            } else {
                $read = $write;
            }
        }
        if (is_string($write)) {
            $write = $connManager->getConnection($write);
        }
        if (is_string($read)) {
            $read = $connManager->getConnection($read);
        }
        return static::createRepo($write, $read);
    }

    /**
     * This will be overrided by child model class.
     *
     * @param Connection $write
     * @param Connection $read
     * @return BaseRepo
     */
    static public function createRepo($write, $read)
    {
        return new BaseRepo($write, $read);
    }


    // Serializable interface methods
    // ===============================
    public function serialize()
    {
        return serialize($this->getData());
    }

    public function unserialize($str)
    {
        $this->setData(unserialize($str));
    }

    /**
     * Create a record object from array.
     */
    public static function fromArray(array $array)
    {
        $record = new static;
        $record->setData($array);
        return $record;
    }
}
