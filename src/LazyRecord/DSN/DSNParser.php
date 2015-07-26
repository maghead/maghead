<?php
namespace LazyRecord\DSN;


/**
 * DataSourceName class provides a basic DSN parser
 */
class DNSParser
{
    protected $dsn;

    protected $prefix;

    protected $attributes = array();

    public function parse($dsn)
    {
        $this->dsn = $dsn;
        if (preg_match('/^(\w+):/', $dsn, $matches)) {
            $this->prefix = $matches[1];
        }
        $str = preg_replace('/^\w+:/','', $dsn);
        $parts = preg_split('/[ ;]/', $str);
        foreach ($parts as $part) {
            list($key, $val) = explode('=',$part);
            $this->attributes[ trim($key) ] = trim($val);
        }
    }

    public function getDriver() {  }
}




