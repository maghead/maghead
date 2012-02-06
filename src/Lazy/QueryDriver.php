<?php
namespace Lazy;

class QueryDriver extends \SQLBuilder\Driver
{
    static $drivers = array();

    static function getInstance($id = 'default')
    {
        if( isset(static::$drivers[ $id ]) )
            return static::$drivers[ $id ];
        
        $driver = new static;
        if( $config = ConnectionManager::getInstance()->getDataSource($id) ) {
            list($driverType) = explode( ':', $config['dsn'] );
            $driver->configure('driver',$driverType);
        }
        return static::$drivers[ $id ] = $driver;
    }

    static function free()
    {
        static::$drivers = array();
    }
}

