<?php

namespace LazyRecord;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use BadMethodCallException;
use PDO;
use PDOException;
use ArrayIterator;
use IteratorAggregate;
use Serializable;
use ArrayAccess;
use Countable;
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
use SerializerKit\XmlSerializer;
use ActionKit;
use Symfony\Component\Yaml\Yaml;

defined('YAML_UTF8_ENCODING') || define('YAML_UTF8_ENCODING', 0);

class QueryException extends RuntimeException
{
    protected $debugInfo = array();

    protected $record;

    public function __construct($msg, BaseModel $record, Exception $previous = null, $debugInfo = array())
    {
        parent::__construct($msg, 0, $previous);
        $this->debugInfo = $debugInfo;
        $this->record = $record;
    }

    public function __debugInfo()
    {
        return [
            'message' => $this->getMessage(),
            'record' => get_class($this->record),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTrace(),
            'previous_trace' => $this->getPrevious()->getTrace(),
            'previous_message' => $this->getPrevious()->getMessage(),
            'debug' => $this->debugInfo,
        ];
    }

    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'record' => get_class($this->record),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTrace(),
            'previous' => $this->getPrevious(),
            'debug' => $this->debugInfo,
        ];
    }
}

class PrimaryKeyNotFoundException extends Exception
{
}

/**
 * Base Model class,
 * every model class extends from this class.
 */
abstract class BaseModel implements
    Serializable,
    ArrayAccess,
    IteratorAggregate,
    Countable
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

    /**
     * The last result object.
     */
    public $lastResult;

    /**
     * @var mixed Current user object
     */
    public $_currentUser;

    /**
     * @var mixed Model-Scope current user object
     *
     *    Book::$currentUser = new YourCurrentUser;
     */
    public static $currentUser;

    // static $schemaCache;

    public $usingDataSource;

    public $alias = 'm';

    public $selected;

    protected $_schema;

    protected $_cachePrefix;

    public static $_cacheInstance;

    /**
     * @var array Mixin classes are emtpy. (MixinDeclareSchema)
     * */
    public static $mixin_classes = array();

    /**
     * @var PDOStatement prepared statement for find by primary key method.
     */
    protected $_preparedFindStm;

    protected $_preparedFindStms = array();

    protected $_preparedCreateStms = array();

    protected $_preparedFindSql;

    private $_readQueryDriver;

    private $_writeQueryDriver;

    private $_readConnection;

    private $_writeConnection;

    /**
     * The constructor.
     *
     * This constructor simply does nothing if no argument is passed.
     *
     * If the first argument is an integer, the record object will try to load 
     * the record by primary key with the given integer.
     *
     * If the first argument is an array, the record object will try to look up
     * the record by treating the array as conditions, just like `where([ ... ])`
     *
     * To avoid record object load the data, you can specify ['load' => false] as the option
     *
     * @param mixed $args    arguments for finding
     * @param array $options constructor options
     */
    public function __construct($args = null, array $options = array())
    {
        // Load the data only when the ID is defined.
        if ($args) {
            if (is_int($args)) {
                $this->load($args);
            } elseif (is_array($args)) {
                if (isset($options['load']) && $options['load'] === false) {
                    $this->setData($args);
                } else {
                    $this->load($args);
                }
            }
        }
    }
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

    public function getCachePrefix()
    {
        if ($this->_cachePrefix) {
            return $this->_cachePrefix;
        }

        return $this->_cachePrefix = get_class($this);
    }

    public function unsetPrimaryKey()
    {
        unset($this->data[static::PRIMARY_KEY]);
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

    /**
     * Provide a basic access controll for model.
     *
     * @param CurrentUserInterface $user  Current user object, but be sure to implement CurrentUserInterface
     * @param string               $right Can be 'create', 'update', 'load', 'delete'
     * @param array                $args  Arguments for operations (update, create, delete.. etc)
     */
    public function currentUserCan($user, $right, $args = array())
    {
        return true;
    }

    public function getDataLabelField()
    {
        if ($this->dataLabelField) {
            return $this->dataLabelField;
        }

        return static::PRIMARY_KEY;
    }

    public function getDataValueField()
    {
        if ($this->dataValueField) {
            return $this->dataValueField;
        }

        return static::PRIMARY_KEY;
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

    /**
     * Get SQL Query Driver by data source id.
     *
     * @param string $dsId Data source id.
     */
    public function getQueryDriver($dsId)
    {
        return ConnectionManager::getInstance()->getQueryDriver($dsId);
    }

    /**
     * Get SQL Query driver object for writing data.
     */
    public function getWriteQueryDriver()
    {
        if ($this->_writeQueryDriver) {
            return $this->_writeQueryDriver;
        }

        return $this->_writeQueryDriver
            = ConnectionManager::getInstance()->getQueryDriver($this->writeSourceId);
    }

    /**
     * Get SQL Query driver object for reading data.
     *
     * @return SQLBuilder\QueryDriver
     */
    public function getReadQueryDriver()
    {
        if ($this->_readQueryDriver) {
            return $this->_readQueryDriver;
        }

        return $this->_readQueryDriver
            = ConnectionManager::getInstance()->getQueryDriver($this->readSourceId);
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
    public function beforeDelete($args)
    {
        return $args;
    }

    public function afterDelete($args)
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
        $schema = $this->getSchema();
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
    public function invokeAllMixinMethods($m, $a)
    {
        foreach (static::$mixin_classes as $mixinClass) {
            // if we found it, just call it and return the result. 
            if (method_exists($mixinClass, $m)) {
                call_user_func_array(array($mixinClass, $m), array_merge(array($this), $a));
            }
        }
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
    public function invokeMixinClassMethod($mixinClass, $m, array $a)
    {
        return call_user_func_array(array($mixinClass, $m), array_merge(array($this), $a));
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
     */
    public function createOrUpdate(array $args, $byKeys = null)
    {
        $pk = static::PRIMARY_KEY;
        $ret = null;
        if ($pk && isset($args[$pk])) {
            $val = $args[$pk];
            $ret = $this->load(array($pk => $val));
        } elseif ($byKeys) {
            $conds = array();
            foreach ((array) $byKeys as $k) {
                if (array_key_exists($k, $args)) {
                    $conds[$k] = $args[$k];
                }
            }
            $ret = $this->load($conds);
        }

        if ($ret && $ret->success
            || ($pk && isset($this->_data[ $pk ]))) {
            return $this->update($args);
        } else {
            return $this->create($args);
        }
    }

    /**
     * Relaod record data by primary key,
     * parameter is optional if you've already defined 
     * the primary key column in this model.
     *
     * @param string $pkId primary key name
     */
    public function reload($pkId = null)
    {
        if ($pkId) {
            return $this->load($pkId);
        } elseif (null === $pkId && $pk = static::PRIMARY_KEY) {
            $pkId = $this->_data[ $pk ];

            return $this->load($pkId);
        } else {
            throw new PrimaryKeyNotFoundException('Primary key is not found, can not reload '.get_class($this));
        }
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
        $ret = null;
        $pk = static::PRIMARY_KEY;
        if ($byKeys) {
            $ret = $this->load(
                array_intersect_key($args,
                    array_fill_keys((array) $byKeys, 1))
            );
        } elseif ($pk && isset($args[$pk])) {
            $val = $args[$pk];
            $ret = $this->load(array($pk => $val));
        } else {
            throw new PrimaryKeyNotFoundException('primary key is not defined.');
        }

        if ($ret && $ret->success
            || ($pk && isset($this->_data[$pk]) && $this->_data[ $pk ])) {
            // is loaded
            return $ret;
        } else {
            // record not found, create
            return $this->create($args);
        }
    }

    /**
     * Run validator to validate column.
     *
     * A validator could be:
     *   1. a ValidationKit validator,
     *   2. a closure
     *   3. a function name
     *
     * The validation result must be returned as in following format:
     *
     *   boolean (valid or invalid, true or false)
     *
     *   array( boolean valid , string message )
     *
     *   ValidationKit\ValidationMessage object.
     *
     * This method returns
     *
     *   (object) {
     *       valid: boolean valid or invalid
     *       field: string field name
     *       message: 
     *   }
     */
    protected function _validateColumn(RuntimeColumn $column, $val, array $args)
    {
        // check for requried columns
        if ($column->required && ($val === '' || $val === null)) {
            return array(
                'valid' => false,
                'message' => sprintf(_('Field %s is required.'), $column->getLabel()),
                'field' => $column->name,
            );
        }

        // XXX: migrate this method to runtime column
        if ($validator = $column->validator) {
            if (is_callable($validator)) {
                $ret = call_user_func($validator, $val, $args, $this);
                if (is_bool($ret)) {
                    return array('valid' => $ret, 'message' => 'Validation failed.', 'field' => $column->name);
                } elseif (is_array($ret)) {
                    return array('valid' => $ret[0], 'message' => $ret[1], 'field' => $column->name);
                } else {
                    throw new Exception('Wrong validation result format, Please returns (valid,message) or (valid)');
                }
            } elseif (is_string($validator) && is_a($validator, 'ValidationKit\\Validator', true)) {
                // it's a ValidationKit\Validator
                $validator = $column->validatorArgs ? new $validator($column->get('validatorArgs')) : new $validator();
                $ret = $validator->validate($val);
                $msgs = $validator->getMessages();
                $msg = isset($msgs[0]) ? $msgs[0] : 'Validation failed.';

                return array('valid' => $ret, 'message' => $msg, 'field' => $column->name);
            } else {
                throw new Exception('Unsupported validator');
            }
        }
        if ($val && $column->validValues) {
            if ($validValues = $column->getValidValues($this, $args)) {
                // sort by index
                if (isset($validValues[0]) && !in_array($val, $validValues)) {
                    return array(
                        'valid' => false,
                        'message' => sprintf('%s is not a valid value for %s', $val, $column->name),
                        'field' => $column->name,
                    );
                }

                /*
                 * Validate for Options
                 * "Label" => "Value",
                 * "Group" => array( "Label" => "Value" )
                
                 * Order with key => value
                 *    value => label
                 */
                else {
                    $values = array_values($validValues);
                    foreach ($values as &$v) {
                        if (is_array($v)) {
                            $v = array_values($v);
                        }
                    }

                    if (!in_array($val, $values)) {
                        return array(
                            'valid' => false,
                            'message' => sprintf(_('%s is not a valid value for %s'), $val, $column->name),
                            'field' => $column->name,
                        );
                    }
                }
            }
        }
    }

    /**
     * Get the RuntimeColumn objects from RuntimeSchema object.
     */
    public function columns()
    {
        return $this->getSchema()->columns;
    }

    public function setCurrentUser(CurrentUserInterface $user)
    {
        $this->_currentUser = $user;

        return $this;
    }

    public function getCurrentUser()
    {
        if ($this->_currentUser) {
            return $this->_currentUser;
        }
        if (static::$currentUser) {
            return static::$currentUser;
        }
    }

    /**
     * Method for creating new record, which is called from 
     * static::create and $record->create.
     *
     * 1. create method calls beforeCreate to 
     * trigger events or filter arguments.
     *
     * 2. it runs filterArrayWithColumns method to filter 
     * arguments with column definitions.
     *
     * 3. use currentUserCan method to check permission.
     *
     * 4. get column definitions and run filters, default value 
     *    builders, canonicalizer, type constraint checkers to build 
     *    a new arguments.
     *
     * 5. use these new arguments to build a SQL query with 
     *    SQLBuilder\QueryBuilder.
     *
     * 6. insert SQL into data source (write)
     *
     * 7. reutrn the operation result.
     *
     * @param array $args data
     *
     * @return Result operation result (success or error)
     */
    public function create(array $args, array $options = array())
    {
        if (empty($args) || $args === null) {
            return $this->reportError('Empty arguments');
        }

        $validationResults = array();
        $validationError = false;
        $schema = $this->getSchema();

        // save $args for afterCreate trigger method
        $origArgs = $args;

        $k = static::PRIMARY_KEY;
        $sql = $vars = null;
        $this->_data = array();
        $stm = null;

        static $cacheable;
        $cacheable = extension_loaded('xarray');

        $conn = $this->getWriteConnection();
        $driver = $this->getWriteQueryDriver();

        // Just a note: Exceptions should be used for exceptional conditions; things you 
        // don't expect to happen. Validating input isn't very exceptional.

        $args = $this->beforeCreate($args);
        if ($args === false) {
            return $this->reportError(_('Create failed'), array(
                'args' => $args,
            ));
        }

        // first, filter the array, arguments for inserting data.
        $args = array_intersect_key($args, array_flip($schema->columnNames));

        // @codegenBlock currentUserCan
        if (!$this->currentUserCan($this->getCurrentUser(), 'create', $args)) {
            return $this->reportError(_('Permission denied. Can not create record.'), array(
                'args' => $args,
            ));
        }
        // @codegenBlockEnd

        // arguments that are will Bind
        $insertArgs = array();
        foreach ($schema->columns as $n => $c) {
            // if column is required (can not be empty)
            //   and default is defined.
            if (!$c->primary && (!isset($args[$n]) || !$args[$n])) {
                if ($val = $c->getDefaultValue($this, $args)) {
                    $args[$n] = $val;
                }
            }

            // if type constraint is on, check type,
            // if not, we should try to cast the type of value, 
            // if type casting is fail, we should throw an exception.

            // short alias for argument value.
            $val = isset($args[$n]) ? $args[$n] : null;

            // if column is required (can not be empty) //   and default is defined.
            // @codegenBlock validateRequire
            if ($c->required && array_key_exists($n, $args) && $args[$n] === null) {
                return $this->reportError("Value of $n is required.");
            }
            // @codegenBlockEnd

            // @codegenBlock typeConstraint
            if ($c->typeConstraint && ($val !== null && !is_array($val) && !$val instanceof Raw)) {
                if (false === $c->checkTypeConstraint($val)) {
                    return $this->reportError("{$val} is not ".$c->isa.' type');
                }
            } elseif ($val !== null && !is_array($val) && !$val instanceof Raw) {
                $val = $c->typeCasting($val);
            }
            // @codegenBlockEnd

            // @codegenBlock filterColumn
            if ($c->filter || $c->canonicalizer) {
                $val = $c->canonicalizeValue($val, $this, $args);
            }
            // @codegenBlockEnd

            // @codegenBlock validateColumn
            if ($validationResult = $this->_validateColumn($c, $val, $args)) {
                $validationResults[$n] = $validationResult;
                if (!$validationResult['valid']) {
                    $validationError = true;
                }
            }
            // @codegenBlockEnd

            if ($val !== null) {
                // Update filtered value back to args
                // Note that we don't deflate a scalar value, this is to prevent the overhead of data reload from database
                // We should try to keep all variables just like the row result we query from database.
                if (is_object($val) || is_array($val)) {
                    $args[$n] = $c->deflate($val, $driver);
                } else {
                    $args[$n] = $val;
                }

                if (is_scalar($val) || is_null($val)) {
                    $insertArgs[$n] = new Bind($n, $driver->cast($val));
                } elseif ($val instanceof Raw) {
                    $insertArgs[$n] = $val;
                    $cacheable = false;
                } else {
                    // deflate objects into string
                    $insertArgs[$n] = new Bind($n, $c->deflate($val, $driver));
                }
            }
        }

        // @codegenBlock handleValidationError
        if ($validationError) {
            return $this->reportError('Validation failed.', array(
                'validations' => $validationResults,
            ));
        }
        // @codegenBlockEnd

        $arguments = new ArgumentArray();

        $cacheKey = null;
        if ($cacheable) {
            $cacheKey = array_keys_join($insertArgs);
            if (isset($this->_preparedCreateStms[$cacheKey])) {
                $stm = $this->_preparedCreateStms[$cacheKey];
                foreach ($insertArgs as $name => $bind) {
                    $arguments->bind($bind);
                }
            }
        }

        try {
            if (!$stm) {
                $query = new InsertQuery();
                $query->into($this->table);
                $query->insert($insertArgs);
                $query->returning($k);
                $sql = $query->toSql($driver, $arguments);
                $stm = $conn->prepare($sql);
                if ($cacheable) {
                    $this->_preparedCreateStms[$cacheKey] = $stm;
                }
            }
            if (false === $stm->execute($arguments->toArray())) {
                return $this->reportError('Record create failed.', array(
                    'validations' => $validationResults,
                    'args' => $args,
                    'sql' => $sql,
                ));
            }
        } catch (Exception $e) {
            var_dump($conn);
            throw new Exception("create fail on " . $this->getWriteSourceId(), 0 , $e);
        }

        $pkId = null;

        if ($driver instanceof PDOPgSQLDriver) {
            $this->_data[$k] = $args[$k] = $pkId = intval($stm->fetchColumn());
        } else {
            $this->_data[$k] = $args[$k] = $pkId = intval($conn->lastInsertId());
        }

        if ($pkId && ((isset($options['reload']) && $options['reload']) || $this->autoReload)) {
            $this->load($pkId);
        } else {
            $this->_data = $args;
        }

        $this->afterCreate($origArgs);

        $stm->closeCursor();

        // collect debug info
        return $this->reportSuccess('Record created.', array(
            'id' => $pkId,
            'sql' => $sql,
            'args' => $args,
            'binds' => $arguments,
            'validations' => $validationResults,
            'type' => Result::TYPE_CREATE,
        ));
    }

    public function setPreferredTable($tableName)
    {
        $this->table = $tableName;
    }

    public function getTable()
    {
        if ($this->table) {
            return $this->table;
        }

        return static::TABLE;
    }

    /**
     * The fast create method does not reload record from created the primary 
     * key.
     *
     * TODO: refactor create code to call fastCreate.
     * TODO: provide rawCreate to create data without validation.
     *
     * @param array $args
     */
    public function fastCreate(array $args)
    {
        return $this->create($args, array('reload' => false));
    }

    /**
     * Find record.
     *
     * @param array condition array
     */
    public function find($pkId)
    {
        $dsId = $this->readSourceId;
        $primaryKey = static::PRIMARY_KEY;
        $conn = $this->getReadConnection();

        if (!$this->_preparedFindStm) {
            $this->_preparedFindStm = $conn->prepare(static::FIND_BY_PRIMARY_KEY_SQL);
        }
        $this->_preparedFindStm->execute([":$primaryKey" => $pkId]);

        if (false === ($this->_data = $this->_preparedFindStm->fetch(PDO::FETCH_ASSOC))) {
            return $this->reportError('Record not found', [
                'sql' => static::FIND_BY_PRIMARY_KEY_SQL,
            ]);
        }
        $this->_preparedFindStm->closeCursor();

        return $this->reportSuccess('Data loaded', array(
            'id' => (isset($this->_data[$primaryKey]) ? $this->_data[$primaryKey] : null),
            'sql' => static::FIND_BY_PRIMARY_KEY_SQL,
            'type' => Result::TYPE_LOAD,
        ));
    }

    public function loadFromCache($args, $ttl = 3600)
    {
        $key = serialize($args);
        if ($cacheData = $this->getCache($key)) {
            $this->_data = $cacheData;
            $pk = static::PRIMARY_KEY;

            return $this->reportSuccess('Data loaded', array(
                'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null),
            ));
        } else {
            $ret = $this->load($args);
            $this->setCache($key, $this->_data, $ttl);

            return $ret;
        }
    }

    public function load($args, array $options = null)
    {
        if (!$this->currentUserCan($this->getCurrentUser(), 'load', $args)) {
            return $this->reportError('Permission denied. Can not load record.', array('args' => $args));
        }

        $dsId = $this->readSourceId;
        $pk = static::PRIMARY_KEY;

        $query = new SelectQuery();
        $query->from($this->table, $this->alias);

        $conn = $this->getReadConnection();
        $driver = $this->getReadQueryDriver();
        $kVal = null;

        // build query from array.
        if (is_array($args)) {
            $query->select($this->selected ?: '*')->where($args);
        } else {
            $kVal = $args;
            $column = $this->getSchema()->getColumn($pk);
            if (!$column) {
                // This should not happend, every schema should have it's own primary key
                // TODO: Create new exception class for this.
                throw new MissingPrimaryKeyException($this->getSchema(), "Primary key $pk is not defined");
            }
            $kVal = $column->deflate($kVal);
            $args = array($pk => $kVal);
            $query->select($this->selected ?: '*')->where($args);
        }

        // generate select * ... for update syntax for MySQL driver
        if (isset($options['for_update']) && $driver instanceof PDOMySQLDriver) {
            $query->forUpdate();
        }

        $arguments = new ArgumentArray();
        $sql = $query->toSql($driver, $arguments);

        // mixed PDOStatement::fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());
        if (false === ($this->_data = $stm->fetch(PDO::FETCH_ASSOC))) {
            // Record not found is not an exception
            return $this->reportError('Record not found', [
                'sql' => $sql,
            ]);
        }

        return $this->reportSuccess('Data loaded', array(
            'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null),
            'sql' => $sql,
            'type' => Result::TYPE_LOAD,
        ));
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $array)
    {
        $record = new static();
        $record->setStashedData($array);

        return $record;
    }

    /**
     * Delete current record, the record should be loaded already.
     *
     * @return Result operation result (success or error)
     */
    public function delete()
    {
        $k = static::PRIMARY_KEY;

        if (!isset($this->_data[$k])) {
            throw new Exception('Record is not loaded, Record delete failed.');
        }

        $kVal = $this->_data[$k];
        if (!$this->currentUserCan($this->getCurrentUser(), 'delete')) {
            return $this->reportError(_('Permission denied. Can not delete record.'), array());
        }

        $dsId = $this->writeSourceId;
        $conn = $this->getWriteConnection();
        $driver = $this->getWriteQueryDriver();

        $this->beforeDelete($this->_data);

        $arguments = new ArgumentArray();

        $query = new DeleteQuery();
        $query->delete($this->table);
        $query->where()->equal($k, $kVal);
        $sql = $query->toSql($driver, $arguments);

        $vars = $arguments->toArray();

        $validationResults = array();

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());

        $this->afterDelete($this->_data);
        $this->clear();

        return $this->reportSuccess('Record deleted', array(
            'sql' => $sql,
            'type' => Result::TYPE_DELETE,
            // XXX 'args' => $arguments->toArray(),
        ));
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
        $schema = $this->getSchema();

        // check if the record is loaded.
        $k = static::PRIMARY_KEY;
        if ($k && !isset($args[ $k ]) && !isset($this->_data[$k])) {
            return $this->reportError('Record is not loaded, Can not update record.', array('args' => $args));
        }

        if (!$this->currentUserCan($this->getCurrentUser(), 'update', $args)) {
            return $this->reportError('Permission denied. Can not update record.', array(
                'args' => $args,
            ));
        }

        // check if we get primary key value
        // here we allow users to specifty primary key value from arguments if the record is not loaded.
        $kVal = null;
        if (isset($args[$k]) && is_scalar($args[$k])) {
            $kVal = intval($args[$k]);
        } elseif (isset($this->_data[$k])) {
            $kVal = intval($this->_data[$k]);
        }

        if (!$kVal) {
            throw new Exception('Primary key value is undefined.');
        }

        $origArgs = $args;
        $dsId = $this->writeSourceId;
        $conn = $this->getWriteConnection();
        $driver = $this->getWriteQueryDriver();
        $sql = null;
        $vars = null;

        $arguments = new ArgumentArray();
        $query = new UpdateQuery();

        $validationError = false;
        $validationResults = array();

        $updateArgs = array();

        $schema = $this->getSchema();

        $args = $this->beforeUpdate($args);
        if ($args === false) {
            return $this->reportError(_('Update failed'), array(
                    'args' => $args,
                ));
        }

            // foreach mixin schema, run their beforeUpdate method,

            $args = array_intersect_key($args, array_flip($schema->columnNames));

        foreach ($schema->columns as $n => $c) {
            if (isset($args[$n])
                    && !$args[$n]
                    && !$c->primary) {
                if ($val = $c->getDefaultValue($this, $args)) {
                    $args[$n] = $val;
                }
            }

                // column validate (value is set.)
                if (!array_key_exists($n, $args)) {
                    continue;
                }

                // if column is required (can not be empty) //   and default is defined.
                if ($c->required && array_key_exists($n, $args) && $args[$n] === null) {
                    return $this->reportError("Value of $n is required.");
                }

                // TODO: Do not render immutable field in ActionKit
                // XXX: calling ::save() might update the immutable columns
                if ($c->immutable) {
                    continue;
                    // TODO: render as a validation results?
                    // continue;
                    // return $this->reportError( "You can not update $n column, which is immutable.", array('args' => $args));
                }

            if ($args[$n] !== null && !is_array($args[$n]) && !$args[$n] instanceof Raw) {
                $args[$n] = $c->typeCasting($args[$n]);
            }

                // The is_array function here is for checking raw sql value.
                if ($args[$n] !== null && !is_array($args[$n]) && !$args[$n] instanceof Raw) {
                    if (false === $c->checkTypeConstraint($args[$n])) {
                        return $this->reportError($args[$n].' is not '.$c->isa.' type');
                    }
                }

            if ($c->filter || $c->canonicalizer) {
                $args[$n] = $c->canonicalizeValue($args[$n], $this, $args);
            }

            if ($validationResult = $this->_validateColumn($c, $args[$n], $args)) {
                $validationResults[$n] = $validationResult;
                if (!$validationResult['valid']) {
                    $validationError = true;
                }
            }

                // deflate the values into query
                /*
                if ($args[$n] instanceof Raw) {
                    $updateArgs[$n] = $args[$n];
                } else {
                    $updateArgs[$n] = $c->deflate($args[$n], $driver);
                }
                */

                // use parameter binding for binding
                $val = $args[$n];
            if (is_scalar($args[$n]) || is_null($args[$n])) {
                $updateArgs[$n] = $bind = new Bind($n, $driver->cast($args[$n]));
                $arguments->bind($bind);
            } elseif ($args[$n] instanceof Raw) {
                $updateArgs[$n] = $args[$n];
            } else {
                $updateArgs[$n] = $bind = new Bind($n, $c->deflate($args[$n], $driver));
                $arguments->bind($bind);
            }
        }

        if ($validationError) {
            return $this->reportError('Validation failed.', array(
                    'validations' => $validationResults,
                ));
        }

        if (empty($updateArgs)) {
            return $this->reportError('Empty args');
        }

            // TODO: optimized to built cache
            $query->set($updateArgs);
        $query->update($this->table);
        $query->where()->equal($k, $kVal);

        $sql = $query->toSql($driver, $arguments);

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());

            // Merge updated data.
            //
            // if $args contains a raw SQL string, 
            // we should reload data from database
            if (isset($options['reload'])) {
                $this->reload();
            } else {
                $this->_data = array_merge($this->_data, $args);
            }

        $this->afterUpdate($origArgs);
        /*
        } 
        catch(PDOException $e)
        {
            throw new QueryException("Record update failed", $this, $e, array(
                'driver' => get_class($driver),
                'args' => $args,
                'sql' => $sql,
                'validations' => $validationResults,
            ));
        }
        */
        return $this->reportSuccess('Updated successfully', array(
            'id' => $kVal,
            'sql' => $sql,
            'args' => $args,
            'type' => Result::TYPE_UPDATE,
        ));
    }

    /**
     * Simply update record without validation and triggers.
     *
     * @param array $args
     */
    public function rawUpdate(array $args)
    {
        $dsId = $this->writeSourceId;
        $conn = $this->getWriteConnection();
        $driver = $this->getWriteQueryDriver();
        $k = static::PRIMARY_KEY;
        $kVal = isset($args[$k])
            ? $args[$k] : isset($this->_data[$k])
            ? $this->_data[$k] : null;

        $arguments = new ArgumentArray();
        $query = new UpdateQuery();
        $query->set($args);
        $query->update($this->table);
        $query->where()
            ->equal($k, $kVal);

        $sql = $query->toSql($driver, $arguments);

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());

        // update current data stash
        $this->_data = array_merge($this->_data, $args);

        return $this->reportSuccess('Update success', array(
            'sql' => $sql,
            'type' => Result::TYPE_UPDATE,
        ));
    }

    /**
     * Simply create record without validation and triggers.
     *
     * @param array $args
     */
    public function rawCreate(array $args)
    {
        $dsId = $this->writeSourceId;
        $conn = $this->getWriteConnection();

        $k = static::PRIMARY_KEY;

        $driver = $this->getWriteQueryDriver();

        $query = new InsertQuery();
        $query->insert($args);
        $query->into($this->table);
        $query->returning($k);

        $arguments = new ArgumentArray();

        $sql = $query->toSql($driver, $arguments);

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());

        $pkId = null;
        if ($driver instanceof PDOPgSQLDriver) {
            $pkId = $stm->fetchColumn();
        } else {
            // lastInsertId is supported in SQLite and MySQL
            $pkId = $conn->lastInsertId();
        }

        $this->_data = $args;
        $this->_data[ $k ] = $pkId;

        return $this->reportSuccess('Create success', array(
            'sql' => $sql,
            'type' => Result::TYPE_CREATE,
        ));
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
        $k = static::PRIMARY_KEY;

        return ($k && !isset($this->_data[$k]))
                ? $this->create($this->_data)
                : $this->update($this->_data)
                ;
    }

    /**
     * Render readable column value.
     *
     * @param string $name column name
     */
    public function display($name)
    {
        if ($c = $this->getSchema()->getColumn($name)) {
            // get raw value
            if ($c->virtual) {
                return $this->get($name);
            }

            return $c->display($this->getValue($name));
        } elseif (isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        // for relationship record
        $val = $this->__get($name);
        if ($val && $val instanceof \LazyRecord\BaseModel) {
            return $val->dataLabel();
        }
    }

    /**
     * deflate data from database.
     *
     * for datetime object, deflate it into DateTime object.
     * for integer  object, deflate it into int type.
     * for boolean  object, deflate it into bool type.
     *
     * @param array $args
     *
     * @return array current record data.
     */
    public function deflateData(array &$args)
    {
        $schema = $this->getSchema();
        foreach ($args as $k => $v) {
            if ($c = $schema->getColumn($k)) {
                $args[ $k ] = $this->_data[ $k ] = $c->deflate($v);
            }
        }

        return $args;
    }

    /**
     * deflate current record data, usually deflate data from database 
     * turns data into objects, int, string (type casting).
     */
    public function deflate()
    {
        $this->deflateData($this->_data);
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
        $stm->execute($args);
        if (false === ($this->_data = $stm->fetch(PDO::FETCH_ASSOC))) {
            return $this->reportError('Data load failed.', array(
                'sql' => $sql,
                'args' => $args,
            ));
        }

        return $this->reportSuccess('Data loaded', array(
            'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null),
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

    /*******************
     * Data Manipulators 
     *********************/

    /**
     * Set column value.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->_data[ $name ] = $value;
    }

    public function set($name, $value)
    {
        $this->_data[ $name ] = $value;
    }

    /**
     * Get inflate value.
     *
     * @param string $name Column name
     */
    public function get($key)
    {
        // relationship id can override value column.
        if ($relation = $this->getSchema()->getRelation($key)) {
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
        return isset($this->_data[$name]);
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
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }
    }

    /**
     * Clear current data stash.
     */
    public function clear()
    {
        $this->_data = array();
    }

    /**
     * get current record data stash.
     *
     * DEPRECATED
     *
     * @return array record data stash
     * @codeCoverageIgnore
     */
    public function getData()
    {
        trigger_error(__METHOD__.' is deprecated.', E_USER_DEPRECATED);

        return $this->_data;
    }

    public function getStashedData()
    {
        return $this->_data;
    }

    /**
     * Set raw data.
     *
     * DEPRECATED
     *
     * @param array $array
     * @codeCoverageIgnore
     */
    public function setData(array $array)
    {
        trigger_error(__METHOD__.' is deprecated.', E_USER_DEPRECATED);
        $this->_data = $array;
    }

    public function setStashedData(array $array)
    {
        $this->_data = $array;
    }

    /**
     * Do we have this column ?
     *
     * @param string $name
     */
    public function __isset($name)
    {
        return isset($this->_data[ $name ])
            || array_key_exists($name, ($this->_data ? $this->_data : array()))
            || isset($this->getSchema()->columns[ $name ])
            || 'schema' === $name
            || $this->getSchema()->getRelation($name)
            ;
    }

    public function getRelationalRecords($key, $relation = null)
    {
        // check for the object cache
        $cacheKey = 'relationship::'.$key;
        if ($this->hasInternalCache($cacheKey)) {
            return clone $this->_cache[ $cacheKey ];
        }

        if (!$relation) {
            $relation = $this->getSchema()->getRelation($key);
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
            if (!$this->hasValue($sColumn)) {
                return;
            }

            // throw new Exception("The value of $sColumn of " . get_class($this) . ' is not defined.');

            $sValue = $this->getValue($sColumn);

            $model = $relation->newForeignModel();
            $model->load(array($fColumn => $sValue));

            return $this->setInternalCache($cacheKey, $model);
        } elseif (Relationship::HAS_MANY === $relation['type']) {
            // TODO: migrate this code to Relationship class.
            $sColumn = $relation['self_column'];
            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];

            if (!$this->hasValue($sColumn)) {
                return;
            }
            // throw new Exception("The value of $sColumn of " . get_class($this) . ' is not defined.');

            $sValue = $this->getValue($sColumn);

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

            if (!$this->hasValue($sColumn)) {
                return;
            }

            $sValue = $this->getValue($sColumn);
            $model = $fpSchema->newModel();
            $ret = $model->load(array($fColumn => $sValue));

            return $this->setInternalCache($cacheKey, $model);
        } elseif (Relationship::MANY_TO_MANY === $relation['type']) {
            $rId = $relation['relation_junction'];  // use relationId to get middle relation. (author_books)
            $rId2 = $relation['relation_foreign'];  // get external relationId from the middle relation. (book from author_books)

            $middleRelation = $this->getSchema()->getRelation($rId);
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
                if (!$ret->success) {
                    throw new Exception("$rId create failed.");
                }

                return $middleRecord;
            });

            return $this->setInternalCache($cacheKey, $collection);
        }

        throw new Exception("The relationship type of $key is not supported.");
    }

    /**
     * Get record data, relational records, schema object or 
     * connection object.
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
        if ($fields) {
            return array_intersect_key($this->_data, array_flip($fields));
        }

        return $this->_data;
    }

    /**
     * return json format data.
     *
     * @return string JSON string
     */
    public function toJson()
    {
        return json_encode($this->_data);
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

        return $ser->encode($this->_data);
    }

    /**
     * Return YAML format data.
     *
     * @return string YAML string
     */
    public function toYaml()
    {
        self::$yamlExtension = extension_loaded('yaml');
        if (self::$yamlExtension) {
            return yaml_emit($this->_data, YAML_UTF8_ENCODING);
        }

        return file_put_contents($yamlFile, "---\n".Yaml::dump($this->_data, $inline = true, $exceptionOnInvalidType = true));
    }

    /**
     * Deflate data and return.
     *
     * @return array
     */
    public function toInflatedArray()
    {
        $data = array();
        $schema = $this->getSchema();
        foreach ($this->_data as $k => $v) {
            $col = $schema->getColumn($k);
            if ($col->isa) {
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
    public function inflateColumnValue($n)
    {
        $value = isset($this->_data[$n]) ? $this->_data[$n] : null;
        if ($c = $this->getSchema()->getColumn($n)) {
            return $c->inflate($value, $this);
        }

        return $value;
    }

    /**
     * Report error.
     *
     * @param string $message Error message.
     * @param array  $extra   Extra data.
     *
     * @return OperationError
     */
    public function reportError($message, $extra = array())
    {
        return $this->lastResult = Result::failure($message, $extra);
    }

    /**
     * Report success.
     *
     * In this method, which pushs result object into ->results array.
     * you can use flushResult() method to clean up these 
     * result objects.
     *
     * @param string $message Success message.
     * @param array  $extra   Extra data.
     *
     * @return Result
     */
    public function reportSuccess($message, $extra = array())
    {
        return $this->lastResult = Result::success($message, $extra);
    }

    public function getLastResult()
    {
        return $this->lastResult;
    }

    public function getDeclareSchema()
    {
        $class = static::SCHEMA_CLASS;

        return new $class();
    }

    public function getSchema()
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
        $schema = $this->getSchema();
        $relation = $schema->getRelation($relationId);

        return $relation->newForeignForeignCollection(
            $schema->getRelation($relation['relation_junction'])
        );
    }

    public function __clone()
    {
        $this->_data = $this->_data;
        $this->autoReload = $this->autoReload;
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
        if (isset($this->_data[$pk])) {
            $args[$pk] = $this->_data[$pk];
        }

        return $this->newAction('Delete', array_merge($this->_data, $args), $options);
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
        $actionClass = \ActionKit\RecordAction\BaseRecordAction::createCRUDClass($class, $type);

        return $actionClass;
    }

    // IteratorAggregate interface method
    // =====================================
    public function getIterator()
    {
        return new ArrayIterator($this->columns);
    }

    public function offsetExists($name)
    {
        return $this->__isset($name);
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetUnset($name)
    {
        unset($this->_data[$name]);
    }

    // Serializable interface methods
    // ===============================

    public function serialize()
    {
        return serialize($this->_data);
    }

    public function unserialize($data)
    {
        $this->_data = unserialize($data);
    }

    // Countable interface

    public function count()
    {
        return count($this->_data);
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function lockWrite($alias = null)
    {
        if (!$alias) {
            $alias = $this->alias;
        }
        // the ::table consts is in the child class.
        if ($alias) {
            $sql = 'LOCK TABLES '.$this->table.' AS '.$alias.' WRITE';
        } else {
            $sql = 'LOCK TABLES '.$this->table.' WRITE';
        }
        $this->getWriteConnection()->query($sql);
    }

    public function lockRead($alias = null)
    {
        if (!$alias) {
            $alias = $this->alias;
        }
        // the ::table consts is in the child class.
        if ($alias) {
            $sql = 'LOCK TABLES '.$this->table.' AS '.$alias.' READ';
        } else {
            $sql = 'LOCK TABLES '.$this->table.' READ';
        }
        $this->getReadConnection()->query($sql);
    }

    public function unlock()
    {
        $readDsId = $this->readSourceId;
        $writeDsId = $this->writeSourceId;
        if ($readDsId === $writeDsId) {
            $this->getReadConnection()->query('UNLOCK TABLES;');
        } else {
            $this->getReadConnection()->query('UNLOCK TABLES;');
            $this->getWriteConnection()->query('UNLOCK TABLES;');
        }
    }

    public function free()
    {
        if ($this->_preparedCreateStms) {
            $this->_preparedCreateStms->closeCursor();
            $this->_preparedCreateStms = null;
        }
        if ($this->_preparedFindStm) {
            $this->_preparedFindStm->closeCursor();
            $this->_preparedFindStm = null;
        }
        foreach ($this->_preparedFindStms as &$stm) {
            $stm->closeCursor();
            $stm = null;
        }
    }
}
