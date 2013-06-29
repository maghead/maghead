<?php
namespace LazyRecord;
use Exception;
use PDOException;
use InvalidArgumentException;
use PDO;
use ArrayIterator;
use IteratorAggregate;
use Serializable;
use ArrayAccess;
use Countable;

use SQLBuilder\QueryBuilder;
use LazyRecord\QueryDriver;
use LazyRecord\OperationResult\OperationError;
use LazyRecord\OperationResult\OperationSuccess;
use LazyRecord\ConnectionManager;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\SchemaLoader;
use LazyRecord\ConfigLoader;

use SerializerKit\XmlSerializer;
use SerializerKit\JsonSerializer;
use SerializerKit\YamlSerializer;

use ValidationKit\ValidationMessage;
use ActionKit;

/**
 * Base Model class,
 * every model class extends from this class.
 *
 */
abstract class BaseModel implements 
    Serializable, 
    ArrayAccess, 
    IteratorAggregate, 
    Countable,
    ExporterInterface
{

    const schema_proxy_class = '';

    protected $_data = array();

    protected $_cache = array();

    /**
     * @var boolean Auto reload record after creating new record
     *
     * Turn off this if you want performance.
     */
    public $autoReload = true;


    /**
     * @var boolean Save operation results
     *
     * Turn off this if you want performance
     */
    public $saveResults = true;


    /**
     * @var OperationResult[] OperationResult pool
     *
     * When saveResult is enabled, operation result object 
     * will be pushed into this array.
     *
     * @see flushResults method to flush result objects.
     */
    public $results = array();

    /**
     * @var mixed Current user object
     *
     */
    public $_currentUser;



    /**
     * @var mixed Model-Scope current user object
     *
     *    Book::$currentUser = new YourCurrentUser;
     *
     */
    static $currentUser;

    // static $schemaCache;

    public $usingDataSource;


    protected $_schema;

    protected $_cachePrefix;

    protected $_joinedRelationships = array();


    static $_cacheInstance;

    /**
     * This constructor simply does nothing if no argument is passed.
     *
     * @param mixed $args arguments for finding
     */
    public function __construct($args = null)
    {
        if ( $args ) {
            $this->_load( $args );
        }
    }





    public function setJoinedRelationships($map)
    {
        $this->_joinedRelationships = $map;
    }

    public function getCachePrefix()
    {
        if ( $this->_cachePrefix ) {
            return $this->_cachePrefix;
        }
        return $this->_cachePrefix = get_class($this);
    }

    /**
     * Use specific data source for data operations.
     *
     * @param string $dsId data source id.
     */
    public function using($dsId)
    {
        $this->usingDataSource = $dsId;
        return $this;
    }


    /**
     * Provide a basic access controll for model
     *
     * @param mixed  $user  Can be your current user object.
     * @param string $right Can be 'create', 'update', 'load', 'delete'
     * @param array  $args  Arguments for operations (update, create, delete.. etc)
     *
     */
    public function currentUserCan($user, $right , $args = array())
    {
        return true;
    }

    /**
     * This is for select widget,
     * returns label value from specific column.
     */
    public function dataLabel() 
    {
        $pk = $this->getSchema()->primaryKey;
        return $this->get($pk);
    }

    /**
     * This is for select widget,
     * returns data key from specific column.
     */
    public function dataKeyValue()
    {
        $pk = $this->getSchema()->primaryKey;
        return $this->get($pk);
    }


    /**
     * Alias method of $this->dataKeyValue()
     */
    public function dataValue()
    {
        return $this->dataKeyValue();
    }

    /**
     * Get SQL Query Driver by data source id.
     *
     * @param string $dsId Data source id.
     *
     * @return SQLBuilder\QueryDriver
     */
    public function getQueryDriver( $dsId )
    {
        return ConnectionManager::getInstance()->getQueryDriver( $dsId );
    }



    /**
     * Get SQL Query driver object for writing data
     *
     * @return SQLBuilder\QueryDriver
     */
    public function getWriteQueryDriver()
    {
        return $this->getQueryDriver($this->getWriteSourceId());
    }


    /**
     * Get SQL Query driver object for reading data
     *
     * @return SQLBuilder\QueryDriver
     */
    public function getReadQueryDriver()
    {
        return $this->getQueryDriver( $this->getReadSourceId() );
    }


    /**
     * Create new QueryBuilder object (inherited from SQLBuilder\QueryBuilder
     *
     * @param string $dsId Data source id , default connection id is 'default'
     *
     * @return SQLBuilder\QueryBuilder
     */
    public function createQuery( $dsId = 'default' )
    {
        $q = new QueryBuilder;
        $q->driver = $this->getQueryDriver($dsId);
        $q->table( $this->getSchema()->table );
        $q->limit(1);
        return $q;
    }


    /**
     * Create executive query builder object, the difference is that
     * An ExecutiveQueryBuilder has an execute method, that trigger a 
     * callback function to execute SQL. the callback function takes
     * a SQL string to insert into database.
     *
     * @param string $dsId data source id.
     *
     * @return ExecutiveQueryBuilder
     */
    public function createExecutiveQuery( $dsId = 'default' )
    {
        $q = new ExecutiveQueryBuilder;
        $q->driver = $this->getQueryDriver( $dsId );
        $q->table( $this->getSchema()->table );
        return $q;
    }





    /**
     * Trigger method for "before creating new record"
     *
     * By overriding this method, you can modify the 
     * arguments that is passed to the query builder.
     *
     * Remember to return the arguments back.
     *
     * @param array $args Arguments
     * @return array $args Arguments
     */
    public function beforeCreate( $args ) 
    {
        return $args;
    }


    /**
     * Trigger for after creating new record
     *
     * @param array $args
     */
    public function afterCreate( $args ) 
    {

    }

    /**
     * Trigger method for
     */
    public function beforeDelete($args)
    {
        return $args;
    }

    public function afterDelete( $args )
    {

    }

    public function beforeUpdate( $args )
    {
        return $args;
    }

    public function afterUpdate( $args )
    {

    }



    /**
     * To support static operation methods like ::create, ::update, we 
     * can not define methods with the same name, so that 
     * we dispatch these methods from the magic method __call.
     *
     * __call method is slower than normal method, because there are
     * one more method table to look up. you should call `_create` method
     * if you need a better performance.
     */
    public function __call($m,$a)
    {
        switch($m) {
        case 'create':
        case 'update':
        case 'load':
        case 'delete':
            return call_user_func_array(array($this,'_' . $m),$a);
            break;
            // XXX: can dispatch methods to Schema object.
            // return call_user_func_array( array(  ) )
            break;
        }

        // dispatch to schema object method first
        $schema = $this->getSchema();
        if( method_exists($schema,$m) ) {
            return call_user_func_array(array($schema,$m),$a);
        }

        // then it's the mixin methods
        if ( isset(static::$mixin_classes) ) { 
            $this->invokeMixinMethod($m, $a);
        }

        // XXX: special case for twig template
        throw new Exception( get_class($this) . ": $m method not found.");
    }

    public function invokeMixinMethod($m,$a) {
        foreach( static::$mixin_classes as $mixinClass ) {
            // if we found it, just call it and return the result. 
            if ( method_exists( $mixinClass , $m ) ) {
                return call_user_func_array( array($mixinClass, $m) , array($this) + $a );
            }
        }
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
    public function createOrUpdate($args, $byKeys = null )
    {
        $pk = $this->getSchema()->primaryKey;
        $ret = null;
        if( $pk && isset($args[$pk]) ) {
            $val = $args[$pk];
            $ret = $this->find(array( $pk => $val ));
        } elseif( $byKeys ) {
            $conds = array();
            foreach( (array) $byKeys as $k ) {
                if( isset($args[$k]) )
                    $conds[$k] = $args[$k];
            }
            $ret = $this->find( $conds );
        }

        if( $ret && $ret->success 
            || ( $pk && isset($this->_data[ $pk ] )) ) 
        {
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
        if( $pkId ) {
            return $this->load( $pkId );
        }
        elseif( null === $pkId && $pk = $this->getSchema()->primaryKey ) {
            $pkId = $this->_data[ $pk ];
            return $this->load( $pkId );
        }
        else {
            throw new Exception("Primary key not found, can not reload record.");
        }
    }


    /**
     * Create a record if the record does not exists
     * Otherwise the record should be updated with the arguments.
     *
     * @param array $args
     * @param array $byKeys it's optional if you defined primary key
     */
    public function loadOrCreate($args, $byKeys = null)
    {
        $pk = $this->getSchema()->primaryKey;

        $ret = null;
        if( $pk && isset($args[$pk]) ) {
            $val = $args[$pk];
            $ret = $this->find(array( $pk => $val ));
        } elseif( $byKeys ) {
            $ret = $this->find(
                array_intersect_key( $args , 
                    array_fill_keys( (array) $byKeys , 1 ))
            );
        }

        if( $ret && $ret->success 
            || ( $pk && isset($this->_data[$pk]) && $this->_data[ $pk ] ) ) 
        {
            // is loaded
            return $ret;
        } else {
            // record not found, create
            return $this->create($args);
        }

    }




    /**
     * Run validator to validate column
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
    protected function _validateColumn($column,$val,$args)
    {
        // check for requried columns
        if( $column->required && ( $val === '' || $val === null ))
        {
            return array( 
                'valid' => false, 
                'message' => sprintf(_('Field %s is required.'), $column->getLabel() ), 
                'field' => $column->name 
            );
        }

        // XXX: migrate this method to runtime column
        if ( $validator = $column->get('validator') ) {
            if ( is_callable($validator) ) {
                $ret = call_user_func($validator, $val, $args, $this );
                if( is_bool($ret) ) {
                    return array( 'valid' => $ret, 'message' => 'Validation failed.' , 'field' => $column->name );
                } elseif( is_array($ret) ) {
                    return array( 'valid' => $ret[0], 'message' => $ret[1], 'field' => $column->name );
                } else {
                    throw new Exception('Wrong validation result format, Please returns (valid,message) or (valid)');
                }
            } elseif ( is_string($validator) && is_a($validator,'ValidationKit\\Validator',true) ) {
                // it's a ValidationKit\Validator
                $validator = $column->validatorArgs ? new $validator($column->get('validatorArgs')) : new $validator;
                $ret = $validator->validate($val);
                $msgs = $validator->getMessages();
                $msg = isset($msgs[0]) ? $msgs[0] : 'Validation failed.';
                return array('valid' => $ret , 'message' => $msg , 'field' => $column->name );
            } else {
                throw new Exception("Unsupported validator");
            }
        }
        if ( $val && ($column->validValues || $column->validValueBuilder ) ) {
            if ( $validValues = $column->getValidValues( $this, $args ) ) {
                // sort by index
                if ( isset($validValues[0]) && ! in_array( $val , $validValues ) ) {
                    return array(
                        'valid' => false,
                        'message' => sprintf("%s is not a valid value for %s", $val , $column->name ),
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
                    $values = array_values( $validValues );
                    foreach( $values as & $v ) {
                        if( is_array($v) ) {
                            $v = array_values($v);
                        }
                    }

                    if( ! in_array( $val , $values ) ) {
                        return array(
                            'valid' => false,
                            'message' => sprintf(_("%s is not a valid value for %s"), $val , $column->name ),
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


    public function setCurrentUser($user)
    {
        $this->_currentUser = $user;
        return $this;
    }

    public function getCurrentUser()
    {
        if( $this->_currentUser )
            return $this->_currentUser;
        if( static::$currentUser ) 
            return static::$currentUser;
    }

    /**
     * Method for creating new record, which is called from 
     * static::create and $record->create.
     *
     * 1. _create method calls beforeCreate to 
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
     * @return OperationResult operation result (success or error)
     */
    public function _create($args, $options = array() )
    {
        if ( empty($args) || $args === null ) {
            return $this->reportError( _('Empty arguments') );
        }

        $validationResults = array();
        $validationFailed = false;
        $schema = $this->getSchema();

        // save $args for afterCreate trigger method
        $origArgs = $args;

        $k = $schema->primaryKey;
        $sql = $vars     = null;
        $this->_data     = array();
        $stm = null;

        try {
            $args = $this->beforeCreate( $args );


            // first, filter the array, arguments for inserting data.
            $args = $this->filterArrayWithColumns($args);

            if( ! $this->currentUserCan( $this->getCurrentUser(), 'create', $args ) ) {
                return $this->reportError( _('Permission denied. Can not create record.') , array( 
                    'args' => $args,
                ));
            }




            $dsId = $this->getWriteSourceId();
            $conn = $this->getConnection( $dsId );
            foreach( $schema->getColumns() as $n => $c ) {
                // if column is required (can not be empty)
                //   and default is defined.
                if( !$c->primary && (!isset($args[$n]) || !$args[$n] ))
                {
                    if( $val = $c->getDefaultValue($this ,$args) ) {
                        $args[$n] = $val;
                    } 
                }

                // if type constraint is on, check type,
                // if not, we should try to cast the type of value, 
                // if type casting is fail, we should throw an exception.


                // short alias for argument value.
                $val = isset($args[$n]) ? $args[$n] : null;

                if( $c->typeConstraint && ( $val !== null && ! is_array($val) ) ) {
                    $c->checkTypeConstraint( $val );
                }
                // try to cast value 
                else if( $val !== null && ! is_array($val) ) {
                    $c->typeCasting( $val );
                }

                if ( $c->filter || $c->canonicalizer ) {
                    $c->canonicalizeValue( $val , $this, $args );
                }


                if ( $validationResult = $this->_validateColumn($c,$val,$args) ) {
                    $validationResults[$n] = (object) $validationResult;
                    if ( ! $validationResult['valid'] ) {
                        $validationFailed = true;
                    }
                }
                if ( $val !== null ) {
                    $args[ $n ] = is_array($val) ? $val : $c->deflate( $val );
                }
            }

            if( $validationFailed ) {
                throw new Exception( "Validation failed." );
            }

            $q = $this->createQuery( $dsId );

            $q->insert($args);
            $q->returning( $k );

            $sql  = $q->build();
            $vars = $q->vars;

            /* get connection, do query */
            $stm = $this->dbPrepareAndExecute($conn, $sql, $vars); // returns $stm
        }
        catch ( Exception $e )
        {
            $msg = $e->getMessage();
            return $this->reportError( ($msg ? $msg : _("Create failed")) , array( 
                'vars'        => $vars,
                'args'        => $args,
                'sql'         => $sql,
                'exception'   => $e,
                'validations' => $validationResults,
            ));
        }

        $driver = $this->getQueryDriver($dsId);


        $pkId = null;
        if( 'pgsql' === $driver->type ) {
            $pkId = $stm->fetchColumn();
        } else {
            $pkId = $conn->lastInsertId();
        }

        if ( $pkId && isset($options['reload']) ) {
            if ( $options['reload'] ) {
                $this->load($pkId);
            }
        } elseif ( $pkId && $this->autoReload ) {
            $this->load($pkId);
        } else {
            $this->_data = $args;
        }
        $this->afterCreate($origArgs);
        $ret = array( 
            'sql' => $sql,
            'args' => $args,
            'vars' => $vars,
            'validations' => $validationResults,
        );
        if( isset($this->_data[ $k ]) ) {
            $ret['id'] = $this->_data[ $k ];
        }
        return $this->reportSuccess('Created', $ret );
    }




    /**
     * The fast create method does not reload record from created the primary 
     * key.
     *
     * TODO: refactor _create code to call fastCreate.
     * TODO: provide rawCreate to create data without validation.
     *
     * @param array $args
     */
    public function fastCreate($args)
    {
        return $this->_create($args, array( 'reload' => false));
    }




    /**
     * Find record
     *
     * @param array condition array
     */
    public function find($args)
    {
        return $this->_load($args);
    }

    public function loadFromCache($args, $ttl = 3600)
    {
        $key = serialize($args);
        if( $cacheData = $this->getCache($key) ) {
            $this->_data = $cacheData;
            $pk = $this->getSchema()->primaryKey;
            return $this->reportSuccess( 'Data loaded', array(
                'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null)
            ));
        }
        else {
            $ret = $this->_load($args);
            $this->setCache($key,$this->_data,$ttl);
            return $ret;
        }
    }

    public function _load($args)
    {
        if( ! $this->currentUserCan( $this->getCurrentUser() , 'load', $args ) ) {
            return $this->reportError( _('Permission denied. Can not load record.') , array( 
                'args' => $args,
            ));
        }

        $dsId  = $this->getReadSourceId();
        $pk    = $this->getSchema()->primaryKey;
        $query = $this->createQuery( $dsId );
        $conn  = $this->getConnection( $dsId );
        $kVal  = null;

        // build query from array.
        if( is_array($args) ) {
            $query->select('*')
                ->whereFromArgs($args);
        }
        else
        {
            $kVal = $args;
            $column = $this->getSchema()->getColumn( $pk );
            if ( ! $column )
                throw new Exception("Primary key $pk is not defined in " . get_class($this->getSchema()) );

            $kVal = $column->deflate( $kVal );
            $args = array( $pk => $kVal );
            $query->select('*')
                ->whereFromArgs($args);
        }

        $sql = $query->build();

        // mixed PDOStatement::fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
        try {
            $stm = $this->dbPrepareAndExecute($conn,$sql,$query->vars);
            // mixed PDOStatement::fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
            if( false === ($this->_data = $stm->fetch( PDO::FETCH_ASSOC )) ) {
                throw new Exception('Data load failed.');
            }
        }
        catch ( Exception $e ) 
        {
            $msg = $e->getMessage();
            return $this->reportError( ($msg ? $msg : _('Data load failed')) , array(
                'sql' => $sql,
                'args' => $args,
                'vars' => $query->vars,
                'exception' => $e,
            ));
        }

        return $this->reportSuccess( 'Data loaded', array( 
            'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null),
            'sql' => $sql,
        ));
    }


    static function fromArray($array)
    {
        $record = new static;
        $record->setData( $array );
        return $record;
    }

    /**
     * Delete current record, the record should be loaded already.
     *
     * @return OperationResult operation result (success or error)
     */
    public function _delete()
    {
        $k = $this->getSchema()->primaryKey;

        if( $k && ! isset($this->_data[$k]) ) {
            return new OperationError('Record is not loaded, Record delete failed.');
        }
        $kVal = isset($this->_data[$k]) ? $this->_data[$k] : null;

        if( ! $this->currentUserCan( $this->getCurrentUser() , 'delete' ) ) {
            return $this->reportError( _('Permission denied. Can not delete record.') , array( ));
        }

        $dsId = $this->getWriteSourceId();
        $conn = $this->getConnection( $dsId );

        $this->beforeDelete( $this->_data );

        $query = $this->createQuery( $dsId );
        $query->delete();
        $query->where()
            ->equal( $k , $kVal );
        $sql = $query->build();

        $validationResults = array();
        try {
            $this->dbPrepareAndExecute($conn,$sql, $query->vars );
        } catch( PDOException $e ) {
            $msg = $e->getMessage();
            return $this->reportError( ($msg ? $msg : _('Delete failed.')) , array(
                'sql'         => $sql,
                'exception'   => $e,
                'validations' => $validationResults,
            ));
        }

        $this->afterDelete( $this->_data );
        $this->clear();
        return $this->reportSuccess( _('Deleted') , array( 
            'sql' => $sql,
            'vars' => $query->vars,
        ));
    }


    /**
     * Update current record
     *
     * @param array $args
     *
     * @return OperationResult operation result (success or error)
     */
    public function _update( $args , $options = array() ) 
    {
        // check if the record is loaded.
        $k = $this->getSchema()->primaryKey;
        if( $k && ! isset($args[ $k ]) 
               && ! isset($this->_data[$k]) ) 
        {
            return $this->reportError('Record is not loaded, Can not update record.');
        }

        if( ! $this->currentUserCan( $this->getCurrentUser() , 'update', $args ) ) {
            return $this->reportError( _('Permission denied. Can not update record.') , array( 
                'args' => $args,
            ));
        }


        // check if we get primary key value
        $kVal = isset($args[$k]) 
            ? $args[$k] : isset($this->_data[$k]) 
            ? $this->_data[$k] : null;


        if( ! $kVal ) {
            return $this->reportError("The value of primary key is undefined.");
        }


        $origArgs = $args;
        $dsId = $this->getWriteSourceId();
        $conn = $this->getConnection( $dsId );
        $sql  = null;
        $vars = null;

        $validationFailed = false;
        $validationResults = array();

        try 
        {
            $args = $this->beforeUpdate($args);

            $args = $this->filterArrayWithColumns($args);


            foreach( $this->getSchema()->getColumns() as $n => $c ) {
                // if column is required (can not be empty)
                //   and default is defined.
                if( isset($args[$n]) 
                    && ! $args[$n]
                    && ! $c->primary )
                {
                    if( $val = $c->getDefaultValue($this ,$args) ) {
                        $args[$n] = $val;
                    }
                }

                // column validate (value is set.)
                if( isset($args[$n]) )
                {
                    if( $args[$n] !== null && ! is_array($args[$n]) ) {
                        $c->typeCasting( $args[$n] );
                    }

                    if ( $c->immutable ) {
                        return $this->reportError( _("You can not update $n column, which is immutable.") , array( 
                            'args' => $args,
                        ));
                    }



                    // xxx: make this optional.
                    if( $args[$n] !== null && ! is_array($args[$n]) && $msg = $c->checkTypeConstraint( $args[$n] ) ) {
                        throw new Exception($msg);
                    }

                    if( $c->filter || $c->canonicalizer ) {
                        $c->canonicalizeValue( $args[$n], $this, $args );
                    }

                    if( $validationResult = $this->_validateColumn($c,$args[$n],$args) ) {
                        $validationResults[$n] = (object) $validationResult;
                        if( ! $validationResult['valid'] ) {
                            $validationFailed = true;
                        }
                    }

                    // deflate
                    $args[ $n ] = is_array($args[$n]) ? $args[$n] : $c->deflate( $args[$n] );
                }

                if ( $validationFailed ) {
                    throw new Exception( "Column $n: Validation failed." );
                }
            }

            $query = $this->createQuery( $dsId );
            $query->update($args)->where()
                ->equal( $k , $kVal );

            $sql  = $query->build();
            $vars = $query->vars;
            $stm  = $this->dbPrepareAndExecute($conn, $sql, $vars);

            // Merge updated data.
            //
            // if $args contains a raw SQL string, 
            // we should reload data from database
            if( isset($options['reload']) ) {
                $this->reload();
            } else {
                $this->_data = array_merge($this->_data,$args);
            }

            $this->afterUpdate($origArgs);
        } 
        catch( Exception $e ) 
        {
            $msg = $e->getMessage();
            return $this->reportError( ($msg ? $msg : 'Update failed') , array(
                'vars' => $vars,
                'args' => $args,
                'sql' => $sql,
                'exception'   => $e,
                'validations' => $validationResults,
            ));
        }

        return $this->reportSuccess( 'Deleted' , array( 
            'id'  => $kVal,
            'sql' => $sql,
            'args' => $args,
            'vars' => $vars,
        ));
    }



    /**
     * Simply update record without validation and triggers.
     *
     * @param array $args
     */
    public function rawUpdate($args) 
    {
        $dsId  = $this->getWriteSourceId();
        $conn  = $this->getConnection( $dsId );
        $k     = $this->getSchema()->primaryKey;
        $kVal = isset($args[$k]) 
            ? $args[$k] : isset($this->_data[$k]) 
            ? $this->_data[$k] : null;

        $query = $this->createQuery( $dsId );
        $query->update($args)->where()
            ->equal( $k , $kVal );

        $sql  = $query->build();
        $vars = $query->vars;
        $stm  = $this->dbPrepareAndExecute($conn, $sql, $vars);

        // update current data stash
        $this->_data = array_merge($this->_data,$args);
    }



    /**
     * Simply create record without validation and triggers.
     *
     * @param array $args
     */
    public function rawCreate($args) 
    {
        $dsId  = $this->getWriteSourceId();
        $conn  = $this->getConnection( $dsId );
        $k     = $this->getSchema()->primaryKey;
        $query = $this->createQuery( $dsId );
        $query->insert($args);
        $query->returning( $k );
        $sql  = $query->build();
        $vars = $query->vars;
        $stm  = $this->dbPrepareAndExecute($conn, $sql, $vars);


        $driver = $this->getQueryDriver($dsId);
        $pkId = null;
        if( 'pgsql' === $driver->type ) {
            $pkId = $stm->fetchColumn();
        } else {
            $pkId = $conn->lastInsertId();
        }


        // update current data stash
        $this->_data = array_merge($this->_data,$args);
        // update primary key value
        $this->_data[ $k ] = $pkId;
    }





    /**
     * Save current data (create or update)
     * if primary key is defined, do update
     * if primary key is not defined, do create
     *
     * @return OperationResult operation result (success or error)
     */
    public function save()
    {
        $k = $this->getSchema()->primaryKey;
        return ( $k && ! isset($this->_data[$k]) )
                ? $this->create( $this->_data )
                : $this->update( $this->_data )
                ;
    }

    /**
     * Render readable column value
     *
     * @param string $name column name
     */
    public function display( $name )
    {
        if ( $c = $this->getSchema()->getColumn( $name ) ) {
            // get raw value
            if ( $c->virtual ) {
                return $this->get($name);
            }
            return $c->display( $this->getValue( $name ) );
        }
        elseif( isset($this->_data[$name]) ) {
            return $this->_data[$name];
        }
        
        // for relationship record
        $val = $this->__get($name);
        if( $val && $val instanceof \LazyRecord\BaseModel ) {
            return $val->dataLabel();
        }
    }


    /**
     * deflate data from database 
     *
     * for datetime object, deflate it into DateTime object.
     * for integer  object, deflate it into int type.
     * for boolean  object, deflate it into bool type.
     *
     * @param array $args
     * @return array current record data.
     */
    public function deflateData(& $args) {
        foreach( $args as $k => $v ) {
            $c = $this->getSchema()->getColumn($k);
            if( $c )
                $args[ $k ] = $this->_data[ $k ] = $c->deflate( $v );
        }
        return $args;
    }

    /**
     * deflate current record data, usually deflate data from database 
     * turns data into objects, int, string (type casting)
     */
    public function deflate()
    {
        $this->deflateData( $this->_data );
    }







    /**
     * get pdo connetion and make a query
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
        if( ! $conn )
            throw new Exception("data source $dsId is not defined.");
        return $conn->query( $sql );
    }

    /**
     * Load record from an sql query
     *
     * @param string $sql  sql statement
     * @param array  $args 
     * @param string $dsId data source id
     *
     *     $result = $record->loadQuery( 'select * from ....', array( ... ) , 'master' );
     *
     * @return OperationResult
     */
    public function loadQuery($sql , $vars = array() , $dsId = null ) 
    {
        if( ! $dsId )
            $dsId = $this->getReadSourceId();
        $conn = $this->getConnection( $dsId );
        $stm = $this->dbPrepareAndExecute($conn, $sql, $vars);
        if( false === ($this->_data = $stm->fetch( PDO::FETCH_ASSOC )) ) {
            return $this->reportError('Data load failed.', array( 
                'sql' => $sql,
                'vars' => $vars,
            ));
        }
        return $this->reportSuccess( 'Data loaded', array( 
            'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null),
            'sql' => $sql
        ));
    }


    /**
     * We should move this method into connection manager.
     *
     * @return PDOStatement
     */
    public function dbPrepareAndExecute($conn, $sql, $args = array() )
    {
        $stm = $conn->prepare( $sql );
        $stm->execute( $args );
        return $stm;
    }


    /**
     * get default connection object (PDO) from connection manager
     *
     * @param string $dsId data source id
     * @return PDO
     */
    public function getConnection( $dsId = 'default' )
    {
        $connManager = ConnectionManager::getInstance();
        return $connManager->getConnection( $dsId ); 
    }


    /**
     * Get PDO connection for writing data.
     *
     * @return PDO
     */
    public function getWriteConnection()
    {
        return ConnectionManager::getInstance()->getConnection( $this->getWriteSourceId() );
    }


    /**
     * Get PDO connection for reading data.
     *
     * @return PDO
     */
    public function getReadConnection()
    {
        return ConnectionManager::getInstance()->getConnection( $this->getReadSourceId() );
    }


    public function getSchemaProxyClass()
    {
        return static::schema_proxy_class;
    }


    /*******************
     * Data Manipulators 
     *********************/

    /**
     * Set column value
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set( $name , $value ) 
    {
        $this->_data[ $name ] = $value; 
    }

    public function set($name, $value)
    {
        $this->_data[ $name ] = $value; 
    }


    /**
     * Get inflate value
     *
     * @param string $name Column name
     */
    public function get($key)
    {
        // relationship id can override value column.
        if ( $relation = $this->getSchema()->getRelation( $key ) ) {
            // cache object cache
            if ( isset($this->_data[ $key ]) 
                && $this->_data[$key] instanceof \LazyRecord\BaseModel ) 
            {
                return $this->_data[$key];
            }

            if ( isset($this->_joinedRelationships[ $key ] ) ) {
                $alias = $this->_joinedRelationships[ $key ];
                $prefix = $alias . '_';
                $stash = array();
                foreach( $this->_data as $k => $v ) {
                    if ( strpos($k,$prefix) === 0 ) {
                        $stash[ substr($k,strlen($prefix)) ] = $v;
                    }
                }
                $model = $relation->newForeignModel();
                $model->setData($stash);
                return $this->_data[ $key ] = $model;
            }

            // use model query to load relational record.
            return $this->getRelationalRecords($key, $relation);
        }
        return $this->inflateColumnValue( $key );
    }






    /**
     * Check if the value exist
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasValue( $name )
    {
        return isset($this->_data[$name]);
    }

    /**
     * Get the raw value from record (without deflator)
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getValue( $name )
    {
        if ( isset($this->_data[$name]) ) {
            return $this->_data[$name];
        }
    }


    /**
     * Clear current data stash
     */
    public function clear()
    {
        $this->_data = array();
    }


    /**
     * get current record data stash
     *
     * @return array record data stash
     */
    public function getData()
    {
        return $this->_data;
    }


    /**
     * set raw data
     *
     * @param array $array
     */
    public function setData($array)
    {
        $this->_data = $array;
    }



    /**
     * Do we have this column ?
     *
     * @param string $name
     */
    public function __isset( $name )
    {
        return isset($this->_data[ $name ]) 
            || array_key_exists($name, ($this->_data ? $this->_data : array()) )
            || isset($this->getSchema()->columns[ $name ]) 
            || 'schema' === $name
            || $this->getSchema()->getRelation( $name )
            ;
    }

    public function getRelationalRecords($key,$relation = null)
    {
        // check for the object cache
        $cacheKey = 'relationship::' . $key;
        if ( $this->hasInternalCache($cacheKey) ) {
            return clone $this->_cache[ $cacheKey ];
        }

        if ( ! $relation ) {
            $relation = $this->getSchema()->getRelation( $key );
        }

        /*
        switch($relation['type']) {
            case SchemaDeclare::has_one:
            case SchemaDeclare::has_many:
            break;
        }
        */
        if ( SchemaDeclare::has_one === $relation['type'] ) 
        {
            $sColumn = $relation['self_column'];

            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];
            $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );
            if ( ! $this->hasValue($sColumn) ) {
                return;
            }
                // throw new Exception("The value of $sColumn of " . get_class($this) . ' is not defined.');

            $sValue = $this->getValue( $sColumn );
            $model = $fpSchema->newModel();
            $model->load(array( $fColumn => $sValue ));
            return $this->setInternalCache($cacheKey,$model);
        }
        elseif( SchemaDeclare::has_many === $relation['type'] )
        {
            // TODO: migrate this code to Relationship class.
            $sColumn = $relation['self_column'];
            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];
            $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );

            if ( ! $this->hasValue($sColumn) ) {
                return;
            }
                // throw new Exception("The value of $sColumn of " . get_class($this) . ' is not defined.');

            $sValue = $this->getValue( $sColumn );

            $collection = $fpSchema->newCollection();
            $collection->where()
                ->equal( 'm.' . $fColumn, $sValue ); // 'm' is the default alias.

            // For if we need to create relational records 
            // though collection object, we need to pre-set 
            // the relational record id.
            $collection->setPresetVars(array( $fColumn => $sValue ));
            return $this->setInternalCache($cacheKey,$collection);
        }
        // belongs to one record
        elseif( SchemaDeclare::belongs_to === $relation['type'] ) {
            $sColumn = $relation['self_column'];
            $fSchema = $relation->newForeignSchema();
            $fColumn = $relation['foreign_column'];
            $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );

            if ( ! $this->hasValue($sColumn) ) {
                return;
            }

            $sValue = $this->getValue( $sColumn );
            $model = $fpSchema->newModel();
            $ret = $model->load(array( $fColumn => $sValue ));
            return $this->setInternalCache($cacheKey, $model);
        }
        elseif( SchemaDeclare::many_to_many === $relation['type'] ) {
            $rId = $relation['relation_junction'];  // use relationId to get middle relation. (author_books)
            $rId2 = $relation['relation_foreign'];  // get external relationId from the middle relation. (book from author_books)

            $middleRelation = $this->getSchema()->getRelation( $rId );
            if ( ! $middleRelation ) {
                throw new InvalidArgumentException("first level relationship of many-to-many $rId is empty");
            }

            // eg. author_books
            $sColumn = $middleRelation['foreign_column'];
            $sSchema = $middleRelation->newForeignSchema();
            $spSchema = SchemaLoader::load( $sSchema->getSchemaProxyClass() );

            $foreignRelation = $spSchema->getRelation( $rId2 );
            if ( ! $foreignRelation )
                throw new InvalidArgumentException( "second level relationship of many-to-many $rId2 is empty." );

            $fSchema = $foreignRelation->newForeignSchema();
            $fColumn = $foreignRelation['foreign_column'];
            $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );

            $collection = $fpSchema->newCollection();

            /**
                * join middle relation ship
                *
                *    Select * from books b (r2) left join author_books ab on ( ab.book_id = b.id )
                *       where b.author_id = :author_id
                */
            $collection->join( $sSchema->getTable() )->alias('b')
                            ->on()
                            ->equal( 'b.' . $foreignRelation['self_column'] , array( 'm.' . $fColumn ) );

            $value = $this->getValue( $middleRelation['self_column'] );
            $collection->where()
                ->equal( 
                    'b.' . $middleRelation['foreign_column'],
                    $value
                );


            /**
                * for many-to-many creation:
                *
                *    $author->books[] = array(
                *        ':author_books' => array( 'created_on' => date('c') ),
                *        'title' => 'Book Title',
                *    );
                */
            $collection->setPostCreate(function($record,$args) use ($spSchema,$rId,$middleRelation,$foreignRelation,$value) {
                // arguments for creating middle-relationship record
                $a = array( 
                    $foreignRelation['self_column']   => $record->getValue( $foreignRelation['foreign_column'] ),  // 2nd relation model id
                    $middleRelation['foreign_column'] => $value,  // self id
                );

                if( isset($args[':' . $rId ] ) ) {
                    $a = array_merge( $args[':' . $rId ] , $a );
                }

                // create relationship
                $middleRecord = $spSchema->newModel();
                $ret = $middleRecord->create($a);
                if( ! $ret->success ) {
                    throw new Exception("$rId create failed.");
                }
                return $middleRecord;
            });
            return $this->setInternalCache($cacheKey,$collection);
        }

        throw new Exception("The relationship type of $key is not supported.");
    }


    /**
     * Get record data, relational records, schema object or 
     * connection object.
     *
     * @param string $key
     */
    public function __get( $key )
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
        $class = static::collection_class;
        return new $class;
    }

    /**
     * return data stash array,
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }


    /**
     * return json format data
     *
     * @return string JSON string
     */
    public function toJson()
    {
        $ser = new JsonSerializer;
        return $ser->encode( $this->_data );
    }


    /**
     * Return xml format data
     *
     * @return string XML string
     */
    public function toXml()
    {
        // TODO: improve element attributes
        $ser = new XmlSerializer;
        return $ser->encode( $this->_data );
    }


    /**
     * Return YAML format data
     *
     * @return string YAML string
     */
    public function toYaml()
    {
        $ser = new YamlSerializer;
        return $ser->encode( $this->_data );
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
        foreach( $this->_data as $k => $v ) {
            $col = $schema->getColumn( $k );
            if ( $col->isa ) {
                $data[ $k ] = $col->inflate( $v );
            } else {
                $data[ $k ] = $v;
            }
        }
        return $data;
    }



    /**
     * Handle static calls for model class.
     *
     * ModelName::delete()
     *     ->where()
     *       ->equal('id', 3)
     *       ->back()
     *      ->execute();
     *
     * ModelName::update( $hash )
     *     ->where()
     *        ->equal( 'id' , 123 )
     *     ->back()
     *     ->execute();
     *
     * ModelName::load( $id );
     *
     */
    public static function __callStatic($m, $a) 
    {
        $called = get_called_class();
        switch( $m ) {
        case 'create':
        case 'update':
        case 'delete':
        case 'load':
            return forward_static_call_array(array( $called , '__static_' . $m), $a);
            break;
        }
        // return call_user_func_array( array($model,$name), $arguments );
    }


    /**
     * Create new record with data array
     *
     * @param array $args data array.
     * @return BaseModel $record
     */
    public static function __static_create($args)
    {
        $model = new static;
        $ret = $model->create($args);
        return $model;
    }

    /**
     * Update record with data array
     *
     * @return SQLBuilder\Expression expression for building where condition sql.
     *
     * Model::update(array( 'name' => 'New name' ))
     *     ->where()
     *       ->equal('id', 1)
     *       ->back()
     *     ->execute();
     */
    public static function __static_update($args) 
    {
        $model = new static;
        $dsId  = $model->getWriteSourceId();
        $conn  = $model->getConnection($dsId);
        $query = $model->createExecutiveQuery($dsId);
        $query->update($args);
        $query->callback = function($builder,$sql) use ($model,$conn) {
            try {
                $stm = $model->dbPrepareAndExecute($conn,$sql,$builder->vars);
            }
            catch ( PDOException $e )
            {
                return new OperationError( 'Update failed: ' .  $e->getMessage() , array( 'sql' => $sql ) );
            }
            return new OperationSuccess('Updated', array( 'sql' => $sql ));
        };
        return $query;
    }


    /**
     * static delete action
     *
     * @return SQLBuilder\Expression expression for building delete condition.
     *
     * Model::delete()
     *    ->where()
     *       ->equal( 'id' , 3 )
     *       ->back()
     *       ->execute();
     */
    public static function __static_delete()
    {
        $model = new static;
        $dsId  = $model->getWriteSourceId();
        $conn  = $model->getConnection($dsId);
        $query = $model->createExecutiveQuery($dsId);
        $query->delete();
        $query->callback = function($builder,$sql) use ($model,$conn) {
            try {
                $stm = $model->dbPrepareAndExecute($conn,$sql,$builder->vars);
            }
            catch ( PDOException $e )
            {
                return new OperationError( 'Delete failed: ' .  $e->getMessage() , array( 'sql' => $sql ) );
            }
            return new OperationSuccess('Deleted', array( 'sql' => $sql ));
        };
        return $query;
    }

    public static function __static_load($args)
    {
        $model = new static;
        $dsId  = $model->getReadSourceId();
        $conn  = $model->getConnection( $dsId );

        if ( is_array( $args ) ) {
            $q = $model->createExecutiveQuery($dsId);
            $q->callback = function($b,$sql) use ($model,$conn) {
                $stm = $model->dbPrepareAndExecute($conn,$sql,$b->vars);
                return $stm->fetchObject( get_class($model) );
            };
            $q->limit(1);
            $q->whereFromArgs($args);
            return $q->execute();
        }
        else {
            $model->load($args);
            return $model;
        }
    }



    /**
     * use array_intersect_key to filter array with column names
     *
     * @param array $args
     * @return array
     */
    public function filterArrayWithColumns( $args , $withVirtual = false )
    {
        return array_intersect_key( $args , $this->getSchema()->getColumns( $withVirtual ) );
    }



    /**
     * Inflate column value 
     *
     * @param string $n Column name
     *
     * @return mixed
     */
    public function inflateColumnValue( $n ) 
    {
        $value = isset($this->_data[ $n ]) ? $this->_data[$n] : null;
        if ( $c = $this->getSchema()->getColumn( $n ) ) {
            return $c->inflate( $value, $this );
        }
        return $value;
    }


    /**
     * Report error 
     *
     * @param string $message Error message.
     * @param array $extra Extra data.
     * @return OperationError
     */
    public function reportError($message,$extra = array() )
    {
        $r = new OperationError($message,$extra);
        if( $this->saveResults ) {
            return $this->results[] = $r;
        }
        return $r;
    }


    /**
     * Report success.
     *
     * In this method, which pushs result object into ->results array.
     * you can use flushResult() method to clean up these 
     * result objects.
     *
     * @param string $message Success message.
     * @param array $extra Extra data.
     * @return OperationSuccess
     */
    public function reportSuccess($message,$extra = array() )
    {
        $r = new OperationSuccess($message,$extra);
        if( $this->saveResults ) {
            return $this->results[] = $r;
        }
        return $r;
    }


    /***************************************
     * Schema related methods
     ***************************************/
    public function loadSchema()
    {
        return SchemaLoader::load( static::schema_proxy_class );
    }

    public function getSchema() 
    {
        if ( $this->_schema ) {
            return $this->_schema;
        } elseif ( @constant('static::schema_proxy_class') ) {
            return $this->_schema = SchemaLoader::load( static::schema_proxy_class );
        } elseif ( method_exists($this,'schema') ) {
            // create dynamic schema declare
            // XXX: note that DynamicSchemaDeclare is using SchemaDeclare/Column
            // not the RuntimeColumn object, so that we can not do deflate or value related operations.
            return $this->_schema = new Schema\DynamicSchemaDeclare($this);
        }
        throw new RuntimeException("schema is not defined in " . get_class($this) );
    }

    /**
     * Duplicated with asCollection() method
     */
    public function newCollection() 
    {
        return $this->getSchema()->newCollection();
    }

    // schema methods
    public function getColumn($n)
    {
        return $this->getSchema()->getColumn($n);
    }


    /**
     * Get column name array from RuntimeSchema object.
     *
     * @return string[] column names
     */
    public function getColumnNames()
    {
        return $this->getSchema()->getColumnNames();
    }


    /**
     * Get column objects from RuntimeSchema object.
     *
     * @return RuntimeColumn[name]
     */
    public function getColumns($withVirtual = false)
    {
        return $this->getSchema()->getColumns( $withVirtual );
    }


    /**
     * Get model label from RuntimeSchema.
     *
     * @return string model label name
     */
    public function getLabel()
    {
        return $this->getSchema()->getLabel();
    }


    /**
     * Get model table
     *
     * @return string model table name
     */
    public function getTable()
    {
        return $this->getSchema()->getTable();
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
     * @param mixed $val cache value
     * @return mixed cached value
     */
    public function setInternalCache($key,$val)
    {
        return $this->_cache[ $key ] = $val;
    }


    /**
     * get internal cache from php memory.
     *
     * @param string $key cache key
     * @return mixed cached value
     */
    public function getInternalCache($key)
    {
        if( isset( $this->_cache[ $key ] ) )
            return $this->_cache[ $key ];
    }

    public function hasInternalCache($key)
    {
        return isset( $this->_cache[ $key ] );
    }



    static public function getCacheInstance()
    {
        if ( self::$_cacheInstance )  {
            return self::$_cacheInstance;
        }
        return self::$_cacheInstance = ConfigLoader::getInstance()->getCacheInstance();
    }


    private function getCache($key)
    {
        if( $cache = self::getCacheInstance() ) {
            return $cache->get( $this->getCachePrefix() . $key);
        }
    }

    private function setCache($key,$val,$ttl = 0)
    {
        if( $cache = self::getCacheInstance() ) {
            $cache->set( $this->getCachePrefix() . $key, $val, $ttl );
        }
        return $val;
    }


    public function getWriteSourceId()
    {
        if ( $this->usingDataSource ) {
            return $this->usingDataSource;
        }
        return $this->getSchema()->getWriteSourceId();
    }

    public function getReadSourceId()
    {
        if ( $this->usingDataSource ) {
            return $this->usingDataSource;
        }
        return $this->getSchema()->getReadSourceId();
    }

    public function getModelClass() {
        return $this->getSchema()->getModelClass();
    }



    public function __clone()
    {
        $this->_data = $this->_data;
        $this->autoReload = $this->autoReload;
    }

    public function popResult() 
    {
        return array_pop($this->results);
    }

    public function pushResult($result) 
    {
        $this->results[] = $result;
    }

    public function flushResults() 
    {
        $r = $this->results;
        $this->results = array();
        return $r;
    }



    public function asCreateAction($args = array())
    {
        return $this->newAction('Create',$args);
    }

    public function asUpdateAction($args = array())
    {
        return $this->newAction('Update',$args);
    }

    public function asDeleteAction($args = array())
    {
        return $this->newAction('Delete',$args);
    }

    /**
     * Create an action from existing record object
     *
     * @param string $type 'create','update','delete'
     *
     * TODO: Move to ActionKit
     */
    public function newAction($type, $args = array() )
    {
        $class = get_class($this);
        $actionClass = \ActionKit\RecordAction\BaseRecordAction::createCRUDClass($class,$type);
        return new $actionClass( $args , $this );
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

    public function offsetSet ( $name , $value )
    {
        $this->set($name,$value);
    }

    public function offsetUnset ( $name )
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


}

