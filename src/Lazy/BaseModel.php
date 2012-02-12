<?php
namespace Lazy;
use SQLBuilder\QueryBuilder;
use Lazy\QueryDriver;

use Lazy\OperationResult\OperationError;
use Lazy\OperationResult\OperationSuccess;
use Exception;
use PDOException;
use PDO;

class BaseModel
{
    public $_result;

    protected $_data;

    public function createQuery()
    {
        $q = new QueryBuilder();
        $q->driver = QueryDriver::getInstance();
        $q->table( $this->_schema->table );
        $q->limit(1);
        return $q;
    }


    public function createExecutiveQuery()
    {
        $q = new ExecutiveQueryBuilder;
        $q->driver = QueryDriver::getInstance();
        $q->table( $this->_schema->table );
        return $q;
    }



    public function _load($kVal)
    {
        $key = $this->_schema->primaryKey;
        $column = $this->_schema->getColumn( $key );
        $kVal = Deflator::deflate( $kVal, $column->isa );

        $query = $this->createQuery();
        $query->select('*')
            ->where()
                ->equal( $key , $kVal );
        $sql = $query->build();

        // mixed PDOStatement::fetch ([ int $fetch_style [, int $cursor_orientation = PDO::FETCH_ORI_NEXT [, int $cursor_offset = 0 ]]] )
        $stm = null;
        try {
            $stm = $this->dbQuery($sql);

            // mixed PDOStatement::fetchObject ([ string $class_name = "stdClass" [, array $ctor_args ]] )
            if( false !== ($this->_data = $stm->fetch( PDO::FETCH_ASSOC )) ) {
                $this->deflateHash( $this->_data );
            }
            else {
                throw new Exception('data fetch failed.');
            }
        }
        catch ( Exception $e ) {
            return $this->reportError( "Data load failed" , array( 
                'sql' => $sql,
                'exception' => $e,
            ));
        }

        return $this->reportSuccess('Data loaded', array( 
            'sql' => $sql
        ));
    }

    public function beforeDelete( $args )
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



    /**
     * create a new record
     *
     * @param array $args data
     *
     * @return OperationResult operation result (success or error)
     */
    protected function _create($args)
    {
        if( empty($args) )
            return $this->reportError( "Empty arguments" );
            
        $args = $this->beforeCreate( $args );
        foreach( $this->_schema->columns as $columnHash ) {
            $c = $this->_schema->getColumn( $columnHash['name'] );

            if( ! $c->primary 
                && $c->requried 
                && ( $c->default || $c->defaultBuilder )
                && ! isset($args[$c->name]) )
            {
                $args[$c->name] = $c->defaultBuilder ? call_user_func( $c->defaultBuidler ) : null;
            }
        }

        // $args = $this->deflateData( $args );

        $q = $this->createQuery();
        $q->insert($args);
        $sql = $q->build();

        /* get connection, do query */
        try {
            $stm = $this->dbQuery($sql);
        }
        catch ( PDOException $e )
        {
            return $this->reportError( "Create failed" , array( 
                'sql' => $sql,
                'exception' => $e,
            ));
        }

        $this->afterCreate( $args );

        $conn = $this->getConnection();
        $k = $this->_schema->primaryKey;
        if( $pkId = $conn->lastInsertId() ) {
            $this->_data[ $k ] = $pkId;
        }
        $this->_data = array_merge( 
            $this->_data,
            $args
        );
        $this->deflate();

        return $this->reportSuccess('Created', array(
            $k => $this->_data[ $k ],
            'sql' => $sql,
        ));
    }

    /**
     * delete current record, the record should be loaded already.
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

        $query = $this->createQuery();
        $query->delete();
        $query->where()
            ->equal( $k , $kVal );
        $sql = $query->build();

        try {
            $this->dbQuery($sql);
        } catch( PDOException $e ) {
            return $this->reportError("Delete failed." , array(
                'sql' => $sql,
                'exception' => $e,
            ));
        }
        return $this->reportSuccess('Deleted');
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

#          $result = $this->validate( 'update', $args );
#          if( $result )
#              return $result;

#          $result = $this->validateUpdate( $args );
#          if( $result )
#              return $result;

        $args = $this->beforeUpdate($args);

        // $args = $this->deflateData( $args ); // apply args to columns

        $query = $this->createQuery();
        $query->update($args)->where()
            ->equal( $k , $kVal );
        $sql = $query->build();

        try {
            $stm = $this->dbQuery($sql);
        } 
        catch( PDOException $e ) {
            return $this->reportError( 'Update failed', array(
                'sql' => $sql,
                'exception' => $e,
            ));
        }

        // merge updated data
        $this->_data = array_merge($this->_data,$args);

        $this->afterUpdate($args);

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
     * resolve record relation ship
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
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn->query( $sql );
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
        $sourceId = $this->getDataSourceId();
        $connManager = ConnectionManager::getInstance();
        return $connManager->getConnection( $sourceId ); // xxx: support read/write connection later
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
        if( $key == '_schema' )
            return SchemaLoader::load( static::schema_proxy_class );

        if( isset( $this->_data[ $key ] ) )
            return $this->_data[ $key ];
    }

    public function __isset( $name )
    {
        return isset($this->_data[ $name ] );
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

    public function __call($m,$a)
    {
        switch($m) {
            case 'create':
            case 'delete':
            case 'update':
            case 'load':
                return call_user_func_array(array($this,'_' . $m),$a);
                break;
        }
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
                $stm = $model->dbQuery($sql);
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
                $stm = $model->dbQuery($sql);
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
                $stm = $model->dbQuery($sql);
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


}



