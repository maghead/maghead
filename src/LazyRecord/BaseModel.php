<?php
namespace LazyRecord;
use Exception;
use PDOException;
use InvalidArgumentException;
use PDO;

use SQLBuilder\QueryBuilder;
use LazyRecord\QueryDriver;
use LazyRecord\OperationResult\OperationError;
use LazyRecord\OperationResult\OperationSuccess;
use LazyRecord\ConnectionManager;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\SchemaLoader;

use SerializerKit\XmlSerializer;
use SerializerKit\JsonSerializer;
use SerializerKit\YamlSerializer;

/**
 * Base Model class,
 * every model class extends from this class.
 *
 */
class BaseModel
    implements ExporterInterface
{
    protected $_data;

    public $_result;

    /**
     * auto reload record after creating new record
     */
    public $_autoReload = true;


    /**
     * @var mixed Current user object
     */
    public $_currentUser;


    /**
     * @var mixed Model-Scope current user object
     *
     *  Book::$currentUser = new YourCurrentUser;
     */
    static $currentUser;

    public function __construct($args = null) 
    {
        if( $args )
            $this->_load( $args );
    }


    /**
     * Provide a basic Access controll
     *
     * @param string $right Can be 'create', 'update', 'load', 'delete'
     * @param mixed  $user  Can be your current user object.
     * @param array  $args  Arguments for operations (update, create, delete.. etc)
     *
     * XXX: is not working in static-call methods:
     *  ::create
     *  ::update
     *  ::delete
     *  ::load
     */
    public function currentUserCan($right,$user = null,$args = null)
    {
        return true;
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




    public function createQuery( $dsId = 'default' )
    {
        $q = new QueryBuilder;
        $q->driver = $this->getQueryDriver($dsId);
        $q->table( $this->_schema->table );
        $q->limit(1);
        return $q;
    }

    public function createExecutiveQuery( $dsId = 'default' )
    {
        $q = new ExecutiveQueryBuilder;
        $q->driver = $this->getQueryDriver( $dsId );
        $q->table( $this->_schema->table );
        return $q;
    }




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

    public function beforeCreate( $args ) 
    {
        return $args;
    }


    /**
     * trigger for after create
     */
    public function afterCreate( $args ) 
    {

    }




    public function __call($m,$a)
    {
        switch($m) {
        case 'create':
        case 'update':
        case 'load':
        case 'delete':
            return call_user_func_array(array($this,'_' . $m),$a);
            break;

            // xxx: can dispatch methods to Schema object.
            // return call_user_func_array( array(  ) )
            break;
        }
        throw new Exception("$m method not found.");
    }



    public function createOrUpdate($args, $byKeys = null )
    {
        $pk = $this->_schema->primaryKey;
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
            || ( $pk && $this->_data[ $pk ] ) ) {
                return $this->update($args);
            } else {
                return $this->create($args);
            }
    }


    public function reload($pkId = null)
    {
        if( $pkId ) {
            $this->load( $pkId );
        }
        elseif( null === $pkId && $pk = $this->_schema->primaryKey ) {
            $pkId = $this->_data[ $pk ];
            $this->load( $pkId );
        }
        else {
            throw new Exception("Primary key not found.");
        }
    }

    public function loadOrCreate($args, $byKeys = null)
    {
        $pk = $this->_schema->primaryKey;

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
            || ( $pk && $this->_data[ $pk ] ) ) 
        {
            // just load
            return $ret;
        } else {
            // record not found, create
            return $this->create($args);
        }

    }



    /**
     * validate validator 
     *
     * @param $c  column object.
     * @param $val value object.
     * @param $args arguments
     * @param $validateFail fail flag.
     */
    protected function _validate_validator($c, $val, $args, & $validateFail )
    {
        $v = call_user_func( $c->validator, $val, $args, $this );
        if( ! $v[0] )
            $validateFail = true;
        return (object) array(
            'success' => $v[0],
            'message' => $v[1],
            'field' => $c->name,
        );
    }

    protected function _validate_validvalues($c, $val, $args, & $validateFail )
    {
        if( $validValues = $c->getValidValues( $this, $args ) ) {
            // sort by index
            if( isset($validValues[0]) && ! in_array( $val , $validValues ) ) {
                $validateFail = true;
                return (object) array(
                    'success' => false,
                    'message' => _( sprintf("%s is not a valid value for %s", $val , $c->name )),
                    'field' => $c->name,
                );
            }
            // order with key => value
            //    value => label
            else {
                $values = array_keys( $validValues );
                if( ! in_array( $val , $values ) ) {
                    $validateFail = true;
                    return (object) array(
                        'success' => false,
                        'message' => _( sprintf("%s is not a valid value for %s", $val , $c->name )),
                        'field' => $c->name,
                    );
                }
            }
        }
    }


    /**
     * return columns
     */
    public function columns()
    {
        static $columns;
        $columns = $this->_schema->columns;
        return $columns;
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
     * Create a new record
     *
     * @param array $args data
     *
     * @return OperationResult operation result (success or error)
     */
    public function _create($args)
    {
        if( empty($args) || $args === null )
            return $this->reportError( _('Empty arguments') );

        // first, filter the array
        $args = $this->filterArrayWithColumns($args);

        if( ! $this->currentUserCan( $this->getCurrentUser() , 'create', $args ) ) {
            return $this->reportError( _('Permission denied. Can not create record.') , array( 
                'args' => $args,
            ));
        }

        $k = $this->_schema->primaryKey;
        $sql = $vars = null;
        $validateFail    = false;
        $this->_data = $validateResults = array();
        $stm = null;


        $dsId = $this->_schema->getWriteSourceId();
        $conn = $this->getConnection( $dsId );

        try {
            $args = $this->beforeCreate( $args );

            foreach( $this->_schema->columns as $columnKey => $hash ) {
                $c = $this->_schema->getColumn( $columnKey );
                $n = $c->name;

                // if column is required (can not be empty)
                //   and default or defaultBuilder is defined.
                if( ! isset($args[$n]) && ! $c->primary )
                {
                    if( $val = $c->getDefaultValue($this ,$args) ) {
                        $args[$n] = $val;
                    } elseif( $c->requried ) {
                        throw new Exception( _( sprintf("%s is required.", $n ) ) );
                    }
                }

                // short alias for argument value.
                $val = isset($args[$n]) ? $args[$n] : null;

                if( $val !== null && ! is_array($val) ) {
                    $c->typeCasting( $val );
                }

                // xxx: make this optional.
                if( $val !== null 
                        && ! is_array($val) 
                        && $c->required )
                        // && $msg = $c->checkTypeConstraint( $val ) ) 
                {
                    throw new Exception("Value of \"$n\" is required");
                }

                if( $c->filter || $c->canonicalizer ) {
                    $c->canonicalizeValue( $val , $this, $args );
                }

                // do validate
                if( $c->validator ) {
                    $validateResults[$n] = 
                        $this->_validate_validator( $c, $val, $args, $validateFail );
                }
                if( $val && ($c->validValues || $c->validValueBuilder) ) {
                    if( $r = $this->_validate_validvalues( $c, $val ,$args, $validateFail ) ) {
                        $validateResults[$n] = $r;
                    }
                }

                if( $val ) {
                    $args[ $n ] = is_array($val) ? $val : $c->deflate( $val );
                }
            }

            if( $validateFail ) {
                throw new Exception( 'Validation Fail' );
            }

            $q = $this->createQuery( $dsId );
            $q->insert($args);
            $q->returning( $k );

            $sql = $q->build();

            /* get connection, do query */
            $vars = $q->vars;
            $stm = $this->dbPrepareAndExecute($conn,$sql,$vars); // returns $stm
            $this->afterCreate( $args );
        }
        catch ( Exception $e )
        {
            return $this->reportError( _("Create failed") , array( 
                'vars'        => $vars,
                'args'        => $args,
                'sql'         => $sql,
                'exception'   => $e,
                'validations' => $validateResults,
            ));
        }

        $driver = $this->getQueryDriver($dsId);

        $pkId = null;
        if( 'pgsql' === $driver->type ) {
            $pkId = $stm->fetchColumn();
        } else {
            $pkId = $conn->lastInsertId();
        }

        if( $this->_autoReload ) {
            // if possible, we should reload the data.
            $pkId ? $this->load($pkId) : $this->_data = $args;
        }

        $ret = array( 
            'sql' => $sql,
            'validations' => $validateResults,
            'args' => $args,
            'vars' => $vars,
        );
        if( isset($this->_data[ $k ]) ) {
            $ret['id'] = $this->_data[ $k ];
        }
        return $this->reportSuccess('Created', $ret );
    }

    public function find($args)
    {
        return $this->_load($args);
    }

    public function _load($args)
    {
        if( ! $this->currentUserCan( $this->getCurrentUser() , 'load', $args ) ) {
            return $this->reportError( _('Permission denied. Can not load record.') , array( 
                'args' => $args,
            ));
        }

        $dsId  = $this->_schema->getReadSourceId();
        $pk    = $this->_schema->primaryKey;
        $query = $this->createQuery( $dsId );
        $conn  = $this->getConnection( $dsId );
        $kVal  = null;
        if( is_array($args) ) {
            $query->select('*')
                ->whereFromArgs($args);
        }
        else {
            $kVal = $args;
            $column = $this->_schema->getColumn( $pk );
            
            if( ! $column ) {
                throw new Exception("Primary key is not defined: $pk .");
            }
            $kVal = $column->deflate( $kVal );
            $args = array( $pk => $kVal );
            $query->select('*')
                ->whereFromArgs($args);
        }

        $sql = $query->build();

        $validateResults = array();

        // mixed PDOStatement::fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
        $stm = null;
        try {
            $stm = $this->dbPrepareAndExecute($conn,$sql,$query->vars);

            // mixed PDOStatement::fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
            if( false === ($this->_data = $stm->fetch( PDO::FETCH_ASSOC )) ) {
                throw new Exception('Data load failed.');
            }
        }
        catch ( Exception $e ) 
        {
            return $this->reportError( 'Data load failed' , array(
                'sql' => $sql,
                'args' => $args,
                'vars' => $query->vars,
                'exception' => $e,
                'validations' => $validateResults,
            ));
        }

        return $this->reportSuccess( 'Data loaded', array( 
            'id' => (isset($this->_data[$pk]) ? $this->_data[$pk] : null),
            'sql' => $sql,
            'validations' => $validateResults,
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
        $k = $this->_schema->primaryKey;

        if( $k && ! isset($this->_data[$k]) ) {
            return new OperationError('Record is not loaded, Record delete failed.');
        }
        $kVal = isset($this->_data[$k]) ? $this->_data[$k] : null;

        if( ! $this->currentUserCan( $this->getCurrentUser() , 'delete' ) ) {
            return $this->reportError( _('Permission denied. Can not delete record.') , array( ));
        }

        $dsId = $this->_schema->getWriteSourceId();
        $conn = $this->getConnection( $dsId );

        $this->beforeDelete( $this->_data );

        $query = $this->createQuery( $dsId );
        $query->delete();
        $query->where()
            ->equal( $k , $kVal );
        $sql = $query->build();

        $validateResults = array();
        try {
            $this->dbPrepareAndExecute($conn,$sql, $query->vars );
        } catch( PDOException $e ) {
            return $this->reportError( _('Delete failed.') , array(
                'sql'         => $sql,
                'exception'   => $e,
                'validations' => $validateResults,
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
     * update current record
     *
     * @param array $args
     *
     * @return OperationResult operation result (success or error)
     */
    public function _update( $args ) 
    {
        // check if the record is loaded.
        $k = $this->_schema->primaryKey;
        if( $k && ! isset($args[ $k ]) && ! isset($this->_data[$k]) ) {
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

        $args = $this->filterArrayWithColumns($args);
        $sql  = null;
        $vars = null;

        $dsId = $this->_schema->getWriteSourceId();
        $conn = $this->getConnection( $dsId );

        try 
        {
            $args = $this->beforeUpdate($args);

            foreach( $this->_schema->columns as $columnHash ) {
                $c = $this->_schema->getColumn( $columnHash['name'] );
                $n = $c->name;

                // if column is required (can not be empty)
                //   and default or defaultBuilder is defined.
                if( isset($args[$n]) 
                    && $c->required
                    && ! $args[$n]
                    && ! $c->primary )
                {
                    if( $val = $c->getDefaultValue($this ,$args) ) {
                        $args[$n] = $val;
                    }
                    elseif( $c->requried ) {
                        throw new Exception( __("%1 is required.", $n) );
                    }
                }


                // column validate (value is set.)
                if( isset($args[$n]) )
                {
                    if( $args[$n] !== null && ! is_array($args[$n]) ) {
                        $c->typeCasting( $args[$n] );
                    }

                    // xxx: make this optional.
                    if( $args[$n] !== null && ! is_array($args[$n]) && $c->required && $msg = $c->checkTypeConstraint( $args[$n] ) ) {
                        throw new Exception($msg);
                    }

                    if( $c->filter || $c->canonicalizer ) {
                        $c->canonicalizeValue( $args[$n], $this, $args );
                    }


                    // do validate
                    if( $c->validator ) {
                        $validateResults[$n] = 
                            $this->_validate_validator( $c, $args[$n], $args, $validateFail );
                    }

                    if( $c->validValues || $c->validValueBuilder ) {
                        if( $r = $this->_validate_validvalues( $c, $args[$n] ,$args, $validateFail ) ) {
                            $validateResults[$n] = $r;
                        }
                    }

                    // deflate
                    $args[ $n ] = is_array($args[$n]) ? $args[$n] : $c->deflate( $args[$n] );
                }
            }

            $query = $this->createQuery( $dsId );

            $query->update($args)->where()
                ->equal( $k , $kVal );

            $sql = $query->build();
            $vars = $query->vars;
            $stm = $this->dbPrepareAndExecute($conn, $sql, $vars);

            // merge updated data
            $this->_data = array_merge($this->_data,$args);
            $this->afterUpdate($args);
        } 
        catch( Exception $e ) 
        {
            return $this->reportError( 'Update failed', array(
                'vars' => $vars,
                'args' => $args,
                'sql' => $sql,
                'exception' => $e,
            ));
        }

        return $this->reportSuccess( _('Deleted') , array( 
            'id'  => $kVal,
            'sql' => $sql,
            'args' => $args,
            'vars' => $vars,
        ));
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
        $k = $this->_schema->primaryKey;
        $doCreate = ( $k && ! isset($this->_data[$k]) );
        return $doCreate
            ? $this->create( $this->_data )
            : $this->update( $this->_data );
    }

    /* pass a value to a column for displaying */
    public function display( $name )
    {
        $c = $this->_schema->getColumn( $name );
        return $c->display( $this->getValue( $name ) );
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
            $c = $this->_schema->getColumn($k);
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
        $stm  = $conn->prepare( $sql );
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
     *
     * @return PDO
     */
    public function getWriteConnection()
    {
        $id = $this->_schema->getWriteSourceId();
        return $this->_connection->getConnection( $id );
    }


    /**
     *
     * @return PDO
     */
    public function getReadConnection()
    {
        $id = $this->_schema->getReadSourceId();
        return $this->_connection->getConnection( $id );
    }


    public function getSchemaProxyClass()
    {
        return static::schema_proxy_class;
    }


    /*******************
     * Data Manipulators 
     *********************/
    public function __set( $name , $value ) 
    {
        $this->_data[ $name ] = $value; 
    }

    public function __get( $key )
    {
        // lazy schema loader, xxx: make this static.
        switch( $key ) {
            case '_schema':
                return SchemaLoader::load( static::schema_proxy_class );
            break;
            case '_connection':
                return ConnectionManager::getInstance();
            break;
        }


        // return relation object
        if( $relation = $this->_schema->getRelation( $key ) ) 
        {
            /*
            switch($relation['type']) {
                case SchemaDeclare::has_one:
                case SchemaDeclare::has_many:
                break;
            }
            */

            if( SchemaDeclare::has_one === $relation['type'] ) 
            {
                $sColumn = $relation['self']['column'];
                $fSchema = new $relation['foreign']['schema'];
                $fColumn = $relation['foreign']['column'];
                $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );
                if( ! $this->hasValue($sColumn) )
                    throw new Exception("The value of $sColumn is not defined.");
                $sValue = $this->getValue( $sColumn );
                $model = $fpSchema->newModel();
                $model->load(array( 
                    $fColumn => $sValue,
                ));
                return $model;
            }
            elseif( SchemaDeclare::has_many === $relation['type'] )
            {
                $sColumn = $relation['self']['column'];
                $fSchema = new $relation['foreign']['schema'];
                $fColumn = $relation['foreign']['column'];
                $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );

                if( ! $this->hasValue($sColumn) )
                    throw new Exception("The value of $sColumn is not defined.");

                $sValue = $this->getValue( $sColumn );

                $collection = $fpSchema->newCollection();
                $collection->where()
                    ->equal( $fColumn, $sValue );

                $collection->setPresetVars(array( 
                    $fColumn => $sValue,
                ));
                return $collection;
            }
            // belongs to one record
            elseif( SchemaDeclare::belongs_to === $relation['type'] ) {
                $sColumn = $relation['self']['column'];
                $fSchema = new $relation['foreign']['schema'];
                $fColumn = $relation['foreign']['column'];
                $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );

                if( ! $this->hasValue($sColumn) )
                    throw new Exception("The value of $sColumn is not defined.");
                $sValue = $this->getValue( $sColumn );
                $model = $fpSchema->newModel();
                $ret = $model->load(array( $fColumn => $sValue ));
                return $model;
            }
            elseif( SchemaDeclare::many_to_many === $relation['type'] ) {
                $rId = $relation['relation']['id'];  // use relationId to get middle relation. (author_books)
                $rId2 = $relation['relation']['id2'];  // get external relationId from the middle relation. (book from author_books)

                $middleRelation = $this->_schema->getRelation( $rId );
                if( ! $middleRelation )
                    throw new \InvalidArgumentException("first level relationship of many-to-many $rId is empty");

                // eg. author_books
                $sColumn = $middleRelation['foreign']['column'];
                $sSchema = new $middleRelation['foreign']['schema'];
                $spSchema = SchemaLoader::load( $sSchema->getSchemaProxyClass() );

                $foreignRelation = $spSchema->getRelation( $rId2 );
                if( ! $foreignRelation )
                    throw new \InvalidArgumentException( "second level relationship of many-to-many $rId2 is empty." );

                $c = $foreignRelation['foreign']['schema'];
                if( ! $c ) 
                    throw new \InvalidArgumentException('foreign schema class is not defined.');

                $fSchema = new $c;
                $fColumn = $foreignRelation['foreign']['column'];
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
                                ->equal( 'b.' . $foreignRelation['self']['column'] , array( 'm.' . $fColumn ) );

                $value = $this->getValue( $middleRelation['self']['column'] );
                $collection->where()
                    ->equal( 
                        'b.' . $middleRelation['foreign']['column'],
                        $value
                    );


                /**
                 * for many-to-many creation:
                 *
                 *    $author->books[] = array(
                 *        :author_books => array( 'created_on' => date('c') ),
                 *        'title' => 'Book Title',
                 *    );
                 */
                $collection->setPostCreate(function($record,$args) use ($spSchema,$rId,$middleRelation,$foreignRelation,$value) {
                    $a = array( 
                        $foreignRelation['self']['column']   => $record->getValue( $foreignRelation['foreign']['column'] ),  // 2nd relation model id
                        $middleRelation['foreign']['column'] => $value,  // self id
                    );

                    if( isset($args[':' . $rId ] ) ) {
                        $a = array_merge( $args[':' . $rId ] , $a );
                    }
                    $ret = $spSchema->newModel()->create($a);
                    if( false === $ret->success ) {
                        throw new Exception("$rId create failed.");
                    }
                });
                return $collection;
            }
            else {
                throw new Exception("The relationship type is not supported.");
            }
        }

        if( isset( $this->_data[ $key ] ) ) {
            return $this->inflateColumnValue( $key );
        }
    }

    public function hasValue( $name )
    {
        return isset($this->_data[$name]);
    }


    /**
     * Get raw value (without deflator)
     */
    public function getValue( $name )
    {
        if( isset($this->_data[$name]) )
            return $this->_data[$name];
    }

    public function __isset( $name )
    {
        return isset($this->_schema->columns[ $name ]) 
            || isset($this->_data[ $name ])
            || '_schema' == $name
            || $this->_schema->getRelation( $name )
            ;
    }

    /**
     * clear current data stash
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


    public function setData($array)
    {
        $this->_data = $array;
    }

    /**
     * return the collection object of current model object.
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

    public function toJson()
    {
        $ser = new JsonSerializer;
        return $ser->encode( $this->_data );
    }

    public function toXml()
    {
        $ser = new XmlSerializer;
        return $ser->encode( $this->_data );
    }

    public function toYaml()
    {
        $ser = new YamlSerializer;
        return $ser->encode( $this->_data );
    }

    /**
     * deflate data and return.
     *
     * @return array
     */
    public function toInflatedArray()
    {
        $data = array();
        foreach( $this->_data as $k => $v ) {
            $col = $this->_schema->getColumn( $k );
            if( $col->isa ) {
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
        $dsId  = $model->_schema->getWriteSourceId();
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
        $dsId  = $model->_schema->getWriteSourceId();
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
        $dsId  = $model->_schema->getReadSourceId();
        $conn  = $model->getConnection( $dsId );

        if( is_array($args) ) {
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

    public function filterArrayWithColumns( $args )
    {
        $schema = $this->_schema;
        $new = array();
        foreach( $args as $k => $v ) {
            if( $schema->getColumn($k) ) {
                $new[ $k ] = $v;
            }
        }
        return $new;
    }

    public function inflateColumnValue( $n ) 
    {
        $value = isset($this->_data[ $n ])
                    ?  $this->_data[ $n ]
                    : null;
        if( $c = $this->_schema->getColumn( $n ) ) {
            return $c->inflate( $value );
        }
        return $value;
    }

    public function reportError($message,$extra = array() )
    {
        return $this->_result = new OperationError($message,$extra);
    }

    public function reportSuccess($message,$extra = array() )
    {
        return $this->_result = new OperationSuccess($message,$extra);
    }


    // slower than _schema
    public function getSchema()
    {
        return SchemaLoader::load( static::schema_proxy_class );
    }

    public function newCollection() 
    {
        return $this->getSchema()->newCollection();
    }

    // _schema methods
    public function getColumn($n)
    {
        return $this->_schema->getColumn($n);
    }

    public function getColumnNames()
    {
        return $this->_schema->getColumnNames();
    }

    public function getColumns()
    {
        return $this->_schema->getColumns();
    }

    public function getLabel()
    {
        return $this->_schema->label;
    }

    public function getTable()
    {
        return $this->_schema->table;
    }

}

