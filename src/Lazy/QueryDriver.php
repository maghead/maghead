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
        if( $type = ConnectionManager::getInstance()->getDataSourceDriver($id) ) {
            $driver->configure('driver',$type);
        }
        return static::$drivers[ $id ] = $driver;
    }

    static function free()
    {
        static::$drivers = array();
    }
}

