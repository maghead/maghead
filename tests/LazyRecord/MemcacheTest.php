<?php

class MemcacheTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if( ! extension_loaded('memcache') ) {
            skip('memcache extension is required.');
        }
    }
    public function test()
    {
        $memcache = new LazyRecord\Memcache(array(
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

