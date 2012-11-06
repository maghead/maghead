<?php
namespace LazyRecord;
use LazyRecord\ConfigLoader;

class Memcache extends \Memcache
{
    public function __construct($config)
    {
        // parent::__construct();
        if( isset($config['servers']) ) {
            foreach( $config['servers'] as $server ) {
                $host = $server['host'];
                $port = isset($server['port']) ? $server['port'] : 11211;
                $this->addServer( $host , $port );
            }
        }
        else {
            $this->addServer('127.0.0.1',11211);
        }
    }
}



