Cache
========

use CacheKit;
use LazyRecord\CacheManager;

LazyRecord\CacheManager::getInstance()->using(
    CacheKit\MemcacheCache::getInstance()
);

