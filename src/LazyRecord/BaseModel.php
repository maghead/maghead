<?php
namespace LazyRecord;
use SQLBuilder\QueryBuilder;

class BaseModel
{
    protected $query;

	public function __construct()
	{

	}

    public function createQuery()
    {
        $this->query = new QueryBuilder();
    }

	public function getSchema()
	{
		static $schema;
		return $schema ? $schema : $schema = LazyRecord\SchemaLoader::getInstance()->load( static::schema_proxy_class );
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



