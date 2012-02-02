<?php
namespace LazyRecord;
use SQLBuilder\QueryBuilder;
use LazyRecord\QueryDriver;

use LazyRecord\OperationResult\OperationError;
use LazyRecord\OperationResult\OperationSuccess;

class BaseModel
{
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

        $q = $this->createQuery();
        $q->insert($args);
        $sql = $q->build();


        /* get connection, do query */
        $conn = $this->getConnection();
        $conn->query( $sql );

    }


    public function deflateArgs( $args ) {
        foreach( $args as $k => $v ) {
            $c = $this->schema->getColumn($k);
            if( $c )
                $args[ $k ] = $this->_data[ $k ] = $c->deflateValue( $v );
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

    public function getConnection()
    {
        // xxx: process for read/write source
        $sourceId = 'default';
        $connManager = ConnectionManager::getInstance();
        return $connManager->getDefault(); // xxx: support read/write connection later
    }




}



