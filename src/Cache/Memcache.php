<?php

namespace Maghead\Cache;

use Memcache as ExtMemcache;

class Memcache extends ExtMemcache
{
    public $flag = null;
    public $expire = null;

    public function __construct(array $config)
    {
        // parent::__construct();
        if (isset($config['servers'])) {
            foreach ($config['servers'] as $server) {
                $host = $server['host'];
                $port = isset($server['port']) ? $server['port'] : 11211;
                $this->addServer($host, $port);
            }
        } else {
            $this->addServer('127.0.0.1', 11211);
        }
        if (isset($config['compress'])) {
            $this->flag = MEMCACHE_COMPRESSED;
        }
        if (isset($config['expire'])) {
            $this->expire = $config['expire'];
        }
    }

    public function set($key, $val, $expire = 0, $flag = null)
    {
        return parent::set($key, $val,
            ($flag ?: $this->flag),
            ($expire ?: $this->expire)
        );
    }
}
