<?php
namespace LazyRecord\SqlBuilder;
use Exception;
use RuntimeException;
use LazyRecord\QueryDriver;
use SQLBuilder\Driver\BaseDriver;

class SqlBuilder
{
    static function create(BaseDriver $driver,$options = array() ) 
    {
        $className = get_class($driver);
        preg_match('/PDO(\w+)Driver$/', $className, $regs);
        if (!$regs[1]) {
            throw new Exception("Can't create sqlbuilder driver class");
        }
        $class = 'LazyRecord\\SqlBuilder\\' . ucfirst(strtolower($regs[1])) . 'Builder';
        return new $class($driver, $options);
    }
}

