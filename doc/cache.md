Cache
========

use CacheKit;
use Maghead\CacheManager;

Maghead\CacheManager::getInstance()->using(
    CacheKit\MemcacheCache::getInstance()
);

