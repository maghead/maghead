<?php
namespace LazyRecord;
use SQLBuilder\QueryBuilder;

class BaseModel
{
    protected $schema;
    protected $query;

	public function __construct()
	{
        $this->schema = $this->getSchema();
	}

	public function getSchema()
	{
		static $schema;
		return $schema ? $schema : $schema = LazyRecord\SchemaLoader::getInstance()->load( static::schema_proxy_class );
	}

    public function createQuery()
    {
        $q = new QueryBuilder();
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
        $args = $this->beforeCreate( $args );

        foreach( $this->schema->columns as $columnHash ) {
            $c = $this->schema->getColumn( $columnHash['name'] );

            if( ! $c->primary && ! isset($args[$name] ) )
                $args[$name] = $c->defaultBuilder ? call_user_func( $c->defaultBuidler ) : null;
        }

        $q = $this->createQuery();
        $q->insert($args);
        $sql = $q->build();

        // get connection and insert


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
        $r = $this->getSchema()->getRelation( $relationId );
        switch( $r['type'] ) {
            case self::many_to_many:
            break;

            case self::has_one:
            break;

            case self::has_many:

            break;
        }
    }

}



