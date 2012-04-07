<?php
namespace LazyRecord\Schema;


class SqlBuilder
{

    /** options **/
    public $rebuild;
    public $clean;


    /* query driver object */
    public $driver;

    /** specific builder object **/
    public $builder;

    public function __construct($driver, $options = array() ) 
    {
        if( isset($options['rebuild']) )
            $this->rebuild = $options['rebuild'];

        if( isset($options['clean']) )
            $this->clean = $options['clean'];

        $this->driver = $driver;
        $this->_createBuilder();
    }


    /**
     * create sql builder for specific driver 
     */
    public function _createBuilder()
    {
        // get driver type
        $type = $this->driver->type;
        if( ! $type )
            throw new Exception("Driver type is not defined.");
        $class = get_class($this) . '\\' . ucfirst($type) . 'Builder';
        $builder = new $class( $this );
        $this->builder = $builder;
    }

    public function build($schema)
    {
        return (array) $this->builder->build( $schema );
    }
}

