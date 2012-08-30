<?php
namespace LazyRecord\Command;
use LazyRecord\ConfigLoader;

class CommandUtils
{
    static $logger;

    static $loader;

    static function init_config_loader() {
        $loader = ConfigLoader::getInstance();
        $loader->loadFromSymbol();
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

    static function get_logger() {
        return static::$logger;
    }

    static function build_basedata($schemas) {
        foreach( $schemas as $schema ) {
            $class = get_class($schema);
            $modelClass = $schema->getModelClass();
            static::log("Creating base data of $modelClass",'green');
            $schema->bootstrap( new $modelClass );
        }

        $loader = ConfigLoader::getInstance();
        if( $seeds = $loader->getSeedScripts() ) {
            foreach( $seeds as $seed ) {
                static::log("Running seed script: $seed",'green');
                if( file_exists($seed) ) {
                    require $seed;
                } 
                elseif( class_exists($seed,true) ) {
                    $seed::seed();
                }
            }
        }
    }

    static function find_schemas_with_arguments($arguments) {
        return \LazyRecord\Utils::getSchemaClassFromPathsOrClassNames( 
            static::$loader, $arguments , static::get_logger() );
    }

    static function schema_classes_to_objects($classes) {
        return array_map(function($class) { return new $class; },$classes);
    }

    static function print_schema_classes($classes) {
        static::log('Found schema classes');
        foreach( $classes as $class ) {
            static::$logger->info( static::$logger->formatter->format($class,'green') , 1 );
        }
    }

    static function build_schemas_with_options($id, $options, $schemas) {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $conn = $connectionManager->getConnection($id);
        $driver = $connectionManager->getQueryDriver($id);
        $builder = \LazyRecord\SqlBuilder\SqlBuilderFactory::create($driver, array( 
            'rebuild' => $options->rebuild,
            'clean' => $options->clean,
        )); // driver

        $sqls = array();
        foreach( $schemas as $schema ) {
            $sqls[] = CommandUtils::build_schema_sql($builder,$schema,$conn);
        }
        return join("\n", $sqls );
    }

    static function build_schema_sql($builder,$schema,$conn) {
        $class = get_class($schema);
        static::log("Building SQL for " . $class,'green');

        $sqls = $builder->build($schema);
        foreach( $sqls as $sql ) {
            static::log( $sql );

            $conn->query( $sql );
            $error = $conn->errorInfo();
            if( $error[1] ) {
                $msg =  $class . ': ' . var_export( $error , true );
                static::$logger->error($msg);
            }
        }
        return "--- Schema $class \n" . join("\n",$sqls);
    }
}


