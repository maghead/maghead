<?php
namespace Lazy;
use Exception;
use PDOException;
use PDO;

use SQLBuilder\QueryBuilder;
use Lazy\QueryDriver;
use Lazy\OperationResult\OperationError;
use Lazy\OperationResult\OperationSuccess;
use Lazy\ConnectionManager;
use Lazy\Schema\SchemaDeclare;


/**
 * Base Model class,
 * every model class extends from this class.
 *
 */
class BaseModel
{
    public $_result;


    /**
     * auto reload record after creating new record
     */
    public $_autoReload = true;

    protected $_data;


    public function __construct($args = null) 
    {
        if( $args )
            $this->load( $args );
    }

    public function getCurrentQueryDriver()
    {
        return ConnectionManager::getInstance()->getQueryDriver( $this->getDataSourceId() );
    }

    public function createQuery()
    {
        $q = new QueryBuilder();
        $q->driver = $this->getCurrentQueryDriver();
        $q->table( $this->_schema->table );
        $q->limit(1);
        return $q;
    }


    public function createExecutiveQuery()
    {
        $q = new ExecutiveQueryBuilder;
        $q->driver = $this->getCurrentQueryDriver();
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
        throw new Exception("$m does not exist.");
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
        if( $v[0] === false )
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
            if( false === in_array( $val , $validValues ) ) {
                $validateFail = true;
                return (object) array(
                    'success' => false,
                    'message' => _( sprintf("%s is not a valid value for %s", $val , $c->name )),
                    'field' => $c->name,
                );
            }
        }
    }


    /**
     * return columns
     */
    public function columns()
    {
        return $this->_schema->columns;
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
        $k = $this->_schema->primaryKey;

        if( empty($args) )
            return $this->reportError( "Empty arguments" );

        // first, filter the array
        $args = $this->filterArrayWithColumns($args);


        $vars            = null;
        $sql             = null;
        $validateFail    = false;
        $validateResults = array();

        try {
            $args = $this->beforeCreate( $args );

            foreach( $this->_schema->columns as $columnHash ) {

                $c = $this->_schema->getColumn( $columnHash['name'] );
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

                if( $val !== null ) {
                    $c->typeCasting( $args[$n] );
                }

                // xxx: make this optional.
                if( $val !== null && $c->required && $msg = $c->checkTypeConstraint( $val ) ) {
                    throw new Exception($msg);
                }

                if( $c->filter || $c->canonicalizer ) {
                    $c->canonicalizeValue( $val , $this, $args );
                }

                // do validate
                if( $c->validator ) {
                    $validateResults[$n] = 
                        $this->_validate_validator( $c, $val, $args, $validateFail );
                }
                if( $c->validValues || $c->validValueBuilder ) {
                    if( $r = $this->_validate_validvalues( $c, $val ,$args, $validateFail ) ) {
                        $validateResults[$n] = $r;
                    }
                }

                if( $val )
                    $args[ $n ] = Inflator::inflate( $val , $c->isa );
            }

            if( $validateFail ) {
                throw new Exception( 'Validation Fail' );
            }

            $q = $this->createQuery();
            $q->insert($args);
            $q->returning( $k );

            $sql = $q->build();

            /* get connection, do query */
            $vars = $q->vars;
            $stm = null;
            $stm = $this->dbPrepareAndExecute($sql,$vars);

            $this->afterCreate( $args );
        }
        catch ( Exception $e )
        {
            return $this->reportError( "Create failed" , array( 
                'vars'        => $vars,
                'args'        => $args,
                'sql'         => $sql,
                'exception'   => $e,
                'validations' => $validateResults,
            ));
        }


        $this->_data = array();
        $conn = $this->getConnection();
        $driver = $this->getCurrentQueryDriver();

        $pkId = null;
        if( $driver->type == 'pgsql' ) {
            $pkId = $stm->fetchColumn();
        } else {
            $pkId = $conn->lastInsertId();
        }


        if( $this->_autoReload ) {
            // if possible, we should reload the data.
            if($pkId) {
                // auto-reload data
                $this->load( $pkId );
            } else {
                $this->_data = $args;
                $this->deflate();
            }
        }

        $ret = array( 
            'sql' => $sql,
            'validations' => $validateResults,
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
        $pk = $this->_schema->primaryKey;
        $query = $this->createQuery();
        $kVal = null;
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
            $kVal = Deflator::deflate( $kVal, $column->isa );
            $args = array( $pk => $kVal );
            $query->select('*')
                ->whereFromArgs($args);
        }

        $sql = $query->build();

        $validateResults = array();

        // mixed PDOStatement::fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
        $stm = null;
        try {
            $stm = $this->dbPrepareAndExecute($sql,$query->vars);

            // mixed PDOStatement::fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
            if( false !== ($this->_data = $stm->fetch( PDO::FETCH_ASSOC )) ) {
                $this->deflate();
            }
            else {
                throw new Exception('data load failed.');
            }
        }
        catch ( Exception $e ) 
        {
            return $this->reportError( 'Data load failed' , array(
                'sql' => $sql,
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

        $this->beforeDelete( $this->_data );

        $query = $this->createQuery();
        $query->delete();
        $query->where()
            ->equal( $k , $kVal );
        $sql = $query->build();

        $validateResults = array();
        try {
            $this->dbPrepareAndExecute($sql, $query->vars );
        } catch( PDOException $e ) {
            return $this->reportError( _('Delete failed.') , array(
                'sql'         => $sql,
                'exception'   => $e,
                'validations' => $validateResults,
            ));
        }

        $this->afterDelete( $this->_data );
        $this->clearData();
        return $this->reportSuccess( _('Deleted'));
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


        // check if we get primary key value
        $kVal = isset($args[$k]) 
            ? $args[$k] : isset($this->_data[$k]) 
            ? $this->_data[$k] : null;

        $args = $this->filterArrayWithColumns($args);
        $sql = null;
        $vars = null;

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


                // column validate
                if( isset($args[$n]) )
                {
                    if( $args[$n] !== null ) {
                        $c->typeCasting( $args[$n] );
                    }

                    // xxx: make this optional.
                    if( $args[$n] !== null && $c->required && $msg = $c->checkTypeConstraint( $args[$n] ) ) {
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
                    if( $r = $this->_validate_validvalues( $c, $args[$n] ,$args, $validateFail ) ) {
                        $validateResults[$n] = $r;
                    }

                    // inflate
                    $args[ $n ] = Inflator::inflate( $args[$n] , $c->isa );
                }
            }


            // $args = $this->deflateData( $args ); // apply args to columns

            $query = $this->createQuery();

            $query->update($args)->where()
                ->equal( $k , $kVal );

            $sql = $query->build();
            $vars = $query->vars;
            $stm = $this->dbPrepareAndExecute($sql,$vars);

            // merge updated data
            $this->_data = array_merge($this->_data,$args);
            $this->deflate();
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

        // throw new Exception( "Update failed." . $dbc->error );
        $result = new OperationSuccess;
        $result->id = $kVal;
        return $result;
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

    public function value($column) 
    {
        if( isset($this->_data[ $column ] ) )
            return $this->_data[ $column ];
        return null;
    }

    /* pass a value to a column for displaying */
    public function display( $name )
    {
        $c = $this->_schema->getColumn( $name );
        return $c->display( $this->value( $name ) );
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
     * XXX: not yet, resolve record relation ship
     *
     * @param string $relationId relation id.
     */
    public function resolveRelation($relationId)
    {
        $r = $this->_schema->getRelation( $relationId );
        switch( $r['type'] ) {
            case self::many_to_many:
            break;

            case self::has_one:
            break;

            case self::has_many:
            break;
        }
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
    public function dbQuery($sql)
    {
        $conn = $this->getConnection();
        // $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn->query( $sql );
    }


    public function dbPrepareAndExecute($sql,$args = array() )
    {
        $conn = $this->getConnection();
        $stm  = $conn->prepare( $sql );
        $stm->execute( $args );
        return $stm;
    }


    // xxx: process for read/write source
    public function getDataSourceId()
    {
        return 'default';
    }

    /**
     * get default connection object (PDO) from connection manager
     *
     * @return PDO
     */
    public function getConnection()
    {
        $connManager = ConnectionManager::getInstance();
        return $connManager->getConnection( $this->getDataSourceId() ); // xxx: support read/write connection later
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
        if( $key === '_schema' )
            return SchemaLoader::load( $this->getSchemaProxyClass() );

        if( isset( $this->_data[ $key ] ) )
            return $this->_data[ $key ];

        // return relation object
        if( $relation = $this->_schema->getRelation( $key ) ) {
            if( SchemaDeclare::has_many === $relation['type'] ) {
                $sColumn = $relation['self']['column'];

                $fSchema = new $relation['foreign']['schema'];
                $fColumn = $relation['foreign']['column'];

                $fpSchema = SchemaLoader::load( $fSchema->getSchemaProxyClass() );
                $collection = $fpSchema->newCollection();
                $collection->where()
                    ->equal( $fColumn, $this->getValue( $sColumn ) );
                return $collection;
            }
        }
    }

    public function getValue( $name )
    {
        if( isset($this->_data[$name]) )
            return $this->_data[$name];
    }

    public function __isset( $name )
    {
        return isset($this->_schema->columns[ $name ]) 
                || isset($this->_data[ $name ])
                || $name === '_schema';
    }

    /**
     * clear current data stash
     */
    public function clearData()
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
     * return the collection object of current model object.
     */
    public function asCollection()
    {
        $class = static::collection_class;
        return new $class;
    }

    /**
     * return data stash array,
     * might need inflate.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

    /**
     * inflate data and return.
     *
     * @return array
     */
    public function toInflateArray()
    {
        $data = array();
        foreach( $this->_data as $k => $v ) {
            $col = $this->_schema->getColumn( $k );
            if( $col->isa ) {
                $data[ $k ] = Inflator::inflate( $v , $col->isa );
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
        $query = $model->createExecutiveQuery();
        $query->update($args);
        $query->callback = function($builder,$sql) use ($model) {
            try {
                $stm = $model->dbPrepareAndExecute($sql,$builder->vars);
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
        $query = $model->createExecutiveQuery();
        $query->delete();
        $query->callback = function($builder,$sql) use ($model) {
            try {
                $stm = $model->dbPrepareAndExecute($sql,$builder->vars);
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
        if( is_array($args) ) {
            $q = $model->createExecutiveQuery();
            $q->callback = function($b,$sql) use ($model) {
                $stm = $model->dbPrepareAndExecute($sql,$b->vars);
                $record = $stm->fetchObject( get_class($model) );
                $record->deflate();
                return $record;
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
            if( $c = $schema->getColumn($k) ) {
                if( $k == 'xxx' )
                    var_dump( $c ); 
                $new[ $k ] = $v;
            }
        }
        return $new;
    }

    public function deflateHash( & $args)
    {
        foreach( $args as $k => $v ) {
            $col = $this->_schema->getColumn( $k );
            $args[ $k ] = $col 
                ? Deflator::deflate( $v , $col->isa ) 
                : $v;
        }
    }


    public function reportError($message,$extra = array() )
    {
        return $this->_result = new OperationError($message,$extra);
    }

    public function reportSuccess($message,$extra = array() )
    {
        return $this->_result = new OperationSuccess($message,$extra);
    }

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

