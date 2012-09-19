<?php
namespace LazyRecord\Command;
use LazyRecord\ConfigLoader;

class CommandUtils
{
    static $logger;

    static $loader;

    static function init_config_loader() {
        $loader = ConfigLoader::getInstance();
        $loader->loadFromSymbol(true); // force loading
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
            if( method_exists($schema,'bootstrap') ) {
                if( $modelClass = $schema->getModelClass() ) {
                    static::log("Creating base data of $modelClass",'green');
                    $schema->bootstrap( new $modelClass );
                }
            }
        }

        foreach( $schemas as $schema ) {
            $seeds = $schema->getSeeds();
            foreach ($seeds as $seedClass ){
                if( class_exists($seedClass,true) ) {
                    static::log("Running seed script: $seedClass",'green');
                    $seedClass::seed();
                } else {
                    static::get_logger()->error("ERROR: Seed script $seedClass not found.");
                }
            }
        }

        $loader = ConfigLoader::getInstance();
        if( $seeds = $loader->getSeedScripts() ) {
            foreach( $seeds as $seed ) {
                $seed = str_replace('::','\\',$seed);
                static::log("Running seed script: $seed",'green');
                if( file_exists($seed) ) {
                    require $seed;
                }
                elseif( class_exists($seed,true) ) {
                    $seed::seed();
                }
                else {
                    static::log("ERROR: Can not run $seed",'red');
                }
            }
        }
    }

    static function find_schemas_with_arguments($arguments) {
        return \LazyRecord\Utils::getSchemaClassFromPathsOrClassNames( 
            static::$loader, $arguments , static::get_logger() );
    }

    static function schema_classes_to_objects($classes) {
        $schemas = array();
        foreach( $classes as $class ) {
            if( is_a($class,'LazyRecord\BaseModel',true) ) {
                $model = new $class;
                $schemas[] = new \LazyRecord\Schema\DynamicSchemaDeclare($model);
            }
            elseif( is_a($class,'LazyRecord\Schema\SchemaDeclare',true) ) {
                $schemas[] = new $class; 
            }
        }
        return $schemas;
    }

    static function print_schema_classes($classes) {
        static::log('Found schema classes');
        foreach( $classes as $class ) {
            static::$logger->debug( static::$logger->formatter->format($class, 'green') , 1 );
        }
    }

    static function build_schemas_with_options($id, $options, $schemas) {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $conn = $connectionManager->getConnection($id);
        $driver = $connectionManager->getQueryDriver($id);
        $builder = \LazyRecord\SqlBuilder\SqlBuilder::create($driver, array( 
            'rebuild' => $options->rebuild,
            'clean' => $options->clean,
        )); // driver

        $sqls = array();
        foreach( $schemas as $schema ) {
            $sqls[] = static::build_schema_sql($builder,$schema,$conn);
        }
        return join("\n", $sqls );
    }

    static function build_schema_sql($builder,$schema,$conn) {
        $class = get_class($schema);
        static::$logger->info('Building SQL for ' . $schema);

        $sqls = $builder->build($schema);
        foreach( $sqls as $sql ) {
            static::$logger->debug($sql);
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


