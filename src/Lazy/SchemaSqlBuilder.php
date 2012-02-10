<?php
namespace Lazy;

class SchemaSqlBuilder
{
    /**
     * builder object
     */
    public $builder;

    /**
     * xxx: should get the driver type from datasource (defined in model schema)
     */
    function __construct($driverType,$driver)
    {
        $builderClass = get_class($this) . '\\' . ucfirst( $driverType ) . 'Driver';
        $this->builder = new $builderClass( $driver );
        $this->builder->driver = $driver;
        $this->builder->driverType = $driverType;
    }

    public function build(Schema\SchemaDeclare $schema)
    {
        $sqls = (array) $this->builder->build( $schema );
        return $sqls;
    }

}




