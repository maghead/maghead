<?php

class MemcacheTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if( ! extension_loaded('memcache') ) {
            skip('memcache extension is required.');
        }
    }

    public function testCacheInstanceFromConfigLoader()
    {
        $config = new LazyRecord\ConfigLoader;
        $config->load('db/config/database.yml');
        if( isset($config->config['cache']) ) {
            $cache = $config->getCacheInstance();
            ok($cache);
        }
    }

    public function test()
    {
        $memcache = new LazyRecord\Cache\Memcache(array(
            'servers' => array(
                array( 'host' => 'localhost', 'port' => 11211)
            )
        ));
        ok($memcache);
        $memcache->set('foo','123');
        ok($memcache->get('foo'));
        is('123',$memcache->get('foo'));
    }
}

