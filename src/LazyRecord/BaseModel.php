<?php
namespace LazyRecord;

class BaseModel
{
	public $schemaClass;
	public $collectionClass;

	public function __construct()
	{

	}

	public function getSchema()
	{
		static $schema;
		return $schema ? $schema : $schema = LazyRecord\SchemaLoader::getInstance()->load( $this->schemaClass );
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



