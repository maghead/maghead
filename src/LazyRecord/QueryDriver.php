<?php
namespace LazyRecord;
use SQLBuilder\Driver;


/**
 * QueryDriver
 *
 * to setup QueryDriver:
 *
 *      $driver = QueryDriver::getInstance('data_source_id');
 *      $driver->configure('driver','pgsql');
 *      $driver->configure('quote_column',true);
 *      $driver->configure('quote_table',true);
 *
 *
 */
class QueryDriver extends Driver
{
    static $drivers = array();


    /**
     * get query driver by data source id
     *
     * @param string $id data source id
     */
    static function getInstance($id = 'default')
    {
        if( isset(static::$drivers[ $id ]) )
            return static::$drivers[ $id ];
        $driver = new static;
        return static::$drivers[ $id ] = $driver;
    }

    static function hasInstance($id = 'default')
    {
        return ( isset(static::$drivers[ $id ]) );
    }


    /**
     * free all driver objects
     */
    static function free()
    {
        static::$drivers = array();
    }

    static function all()
    {
        return static::$drivers;
    }

}

