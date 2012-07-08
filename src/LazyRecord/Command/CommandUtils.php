<?php
namespace LazyRecord\Command;
use LazyRecord\ConfigLoader;

class CommandUtils
{
    static $logger;

    static $loader;

    static function init_config_loader() {
        $loader = ConfigLoader::getInstance();
        $loader->load();
        $loader->initForBuild();
        return static::$loader = $loader;
    }

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

    static function get_logger($logger) {
        return static::$logger;
    }

    static function create_basedata($schemas) {
        foreach( $schemas as $schema ) {
            $class = get_class($schema);
            $modelClass = $schema->getModelClass();

            static::log("Creating base data for $modelClass",'green');
            $schema->bootstrap( new $modelClass );
        }
    }

    static function find_schemas_with_arguments($arguments) {
        return \LazyRecord\Utils::getSchemaClassFromPathsOrClassNames( 
            static::$loader, $arguments , static::get_logger() );
    }

    static function schema_classes_to_objects($classes) {
        return array_map(function($class) { return new $class; },$classes);
    }

    static function build_schema_sql($schemas) {

    }
}


