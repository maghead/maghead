<?php

class CacheManagerTest extends PHPUnit_Framework_TestCase
{
    function testCache()
    {
        if( ! extension_loaded('memcache') )
            return;

        $cache = CacheKit\MemcacheCache::getInstance();
        ok($cache);

        LazyRecord\CacheManager::getInstance()->using($cache);
    }
}

