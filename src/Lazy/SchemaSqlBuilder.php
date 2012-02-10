<?php
namespace Lazy;

class SchemaSqlBuilder
{
    public $builder;
    public $type;


    /**
     * xxx: should get the driver type from datasource (defined in model schema)
     */
    function __construct($driverType)
    {
        $builderClass = get_class($this) . '\\' . ucfirst( $driverType ) . 'Driver';
        $this->builder = new $builderClass;
        $this->type = $driverType;
    }

    public function build(Schema\SchemaDeclare $schema)
    {
        $sqls = (array) $this->builder->build( $schema );
        return $sqls;
    }

}




