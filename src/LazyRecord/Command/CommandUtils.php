<?php
namespace LazyRecord\Command;
use LazyRecord\ConfigLoader;
use LazyRecord\Utils;
use LazyRecord\SqlBuilder\BaseBuilder;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\DatabaseBuilder;
use LazyRecord\ConnectionManager;
use CLIFramework\Logger;
use PDO;

class CommandUtils
{
    static $logger;

    static $loader;

    static function set_logger($logger) {
        static::$logger = $logger;
    }

    static function log($msg , $style = null) {
        if( static::$logger ) {
            if( $style ) {
                static::$logger->info( static::$logger->formatter->format( $msg ,'green') );
            } else {
                static::$logger->info($msg);
            }
        }
    }

    static function get_logger() {
        return static::$logger;
    }

    static function print_schema_classes($classes) {
        static::log('Found schema classes');
        foreach( $classes as $class ) {
            static::$logger->debug( static::$logger->formatter->format($class, 'green') , 1 );
        }
    }
}


