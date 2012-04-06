<?php
namespace LazyRecord\Schema;


class SqlBuilder
{
    public $rebuild;

    public $builder;

    public function __construct($driver, $options = array() ) 
    {
        if( isset($options['rebuild']) )
            $this->rebuild = $options['rebuild'];
    }

    public function createBuilder()
    {
        // get driver type

    }

    public function build($schema)
    {

    }
}

class SqlBuilder2
{
    /**
     * builder object
     */
    public $builder;


    /**
     * should we rebuild (drop existing tables?)
     */
    public $rebuild = true;

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

    public function build(SchemaDeclare $schema)
    {
        $sqls = (array) $this->builder->build( $schema , $this->rebuild );
        return $sqls;
    }

}




