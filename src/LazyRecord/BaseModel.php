<?php
namespace LazyRecord;
use SQLBuilder\QueryBuilder;
use LazyRecord\QueryDriver;

use LazyRecord\OperationResult\OperationError;
use LazyRecord\OperationResult\OperationSuccess;
use PDOException;
use PDO;

class BaseModel
{
    private $_data;
    public $schema;
    protected $query;

	public function __construct()
	{
        $this->schema = $this->getSchema();
	}

	public function getSchema()
	{
		static $schema;
        $schemaClass = static::schema_proxy_class;
		return $schema ?: $schema = new $schemaClass;
	}

    public function createQuery()
    {
        $q = new QueryBuilder();
        $q->driver = QueryDriver::getInstance();
        $q->table( $this->schema->table );
        $q->limit(1);
        return $q;
    }

    public function beforeCreate( $args ) 
    {
        return $args;
    }

    public function afterCreate( $args ) 
    {

    }

    public function load($kVal)
    {
        $key = $this->schema->primaryKey;
        $column = $this->schema->getColumn( $key );
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
            $data = $stm->fetch( PDO::FETCH_ASSOC );
            $this->_data = $data;
        }
        catch ( PDOException $e ) {
            return new OperationError( "Load data failed" );
        }
        return new OperationSuccess;
    }

    public function create($args)
    {
        if( empty($args) )
            return new OperationError( "Empty arguments" );
            
        $args = $this->beforeCreate( $args );
        foreach( $this->schema->columns as $columnHash ) {
            $c = $this->schema->getColumn( $columnHash['name'] );

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
            /*
            if ($e->getCode == '2A000') {
                echo "Syntax Error: " . $e->getMessage();
            }
            */
            return new OperationError( 'Create failed: ' .  $e->getMessage() );
        }
        $this->afterCreate( $args );

        $conn = $this->getConnection();
        $result = new OperationSuccess;
        $result->id = $conn->lastInsertId();
        return $result;
    }

    public function delete()
    {
        $k = $this->schema->primaryKey;
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
            return new OperationError("Delete failed.");
        }
        return new OperationSuccess;
    }

    public function update( $args ) 
    {
        $k = $this->schema->primaryKey;
        if( $k && ! isset($args[ $k ]) && ! isset($this->_data[$k]) ) {
            return new OperationError('Record is not loaded, Can not update record.');
        }

        $kVal = isset($args[$k]) 
            ? $args[$k] : isset($this->_data[$k]) 
            ? $this->_data[$k] : null;

#          $result = $this->validate( 'update', $args );
#          if( $result )
#              return $result;

#          $result = $this->validateUpdate( $args );
#          if( $result )
#              return $result;


#          $args = $this->beforeUpdate( $args );
#          $args = $this->deflateData( $args ); // apply args to columns

        $query = $this->createQuery();
        $query->update($args)->where()
            ->equal( $k , $kVal );
        $sql = $query->build();

        try {
            $stm = $this->dbQuery($sql);
        } 
        catch( PDOException $e ) {
            return new OperationError( 'Update failed: ' .  $e->getMessage() );
        }

        // merge updated data
        $this->_data = array_merge($this->_data,$args);

        // throw new Exception( "Update failed." . $dbc->error );
        $result = new OperationSuccess;
        $result->id = $kVal;
        return $result;
    }



    /**
     * deflate data from database 
     *
     * for datetime object, deflate it into DateTime object.
     * for integer  object, deflate it into int type.
     * for boolean  object, deflate it into bool type.
     */
    public function deflateData( $args ) {
        foreach( $args as $k => $v ) {
            $c = $this->schema->getColumn($k);
            if( $c )
                $args[ $k ] = $this->_data[ $k ] = $c->deflate( $v );
        }
        return $args;
    }




    public function resolveRelation($relationId)
    {
        $r = $this->schema->getRelation( $relationId );
        switch( $r['type'] ) {
            case self::many_to_many:
            break;

            case self::has_one:
            break;

            case self::has_many:

            break;
        }
    }

    public function dbQuery($sql)
    {
        $conn = $this->getConnection();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn->query( $sql );
    }


    public function getConnection()
    {
        // xxx: process for read/write source
        $sourceId = 'default';
        $connManager = ConnectionManager::getInstance();
        return $connManager->getDefault(); // xxx: support read/write connection later
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
        if( isset( $this->_data[ $key ] ) )
            return $this->_data[ $key ];
    }

    public function __isset( $name )
    {
        return isset($this->_data[ $name ] );
    }

    public function resetData()
    {
        $this->_data = array();
    }

    public function getData()
    {
        return $this->_data;
    }


    /**
     * support static method of 'delete', 'find' 
     */
    public static function __callStatic($n, $a) 
    {
        // $model = new static;
        // return call_user_func_array( array($model,$name), $arguments );
    }

    public function __call($m,$a)
    {

    }



}



