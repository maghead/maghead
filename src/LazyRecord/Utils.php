<?php
namespace LazyRecord;
use LazyRecord\Schema\SchemaFinder;

class Utils
{
    /**
     * Returns schema objects
     *
     * @return array schema objects
     */
    public static function getSchemaClassFromPathsOrClassNames($loader, $args, $logger = null)
    {
        if( count($args) && ! file_exists($args[0]) ) {
            $classes = array();
            // it's classnames
            foreach( $args as $class ) {
                // call class loader to load
                if( class_exists($class,true) ) {
                    $classes[] = $class;
                }
                else {
                    if ( $logger ) {
                        $logger->warn( "$class not found." );
                    } else {
                        echo ">>> $class not found.\n";
                    }
                }
            }
            return ClassUtils::schema_classes_to_objects( $classes );
        }
        else {
            $finder = new SchemaFinder;
            if ( $logger ) {
                $finder->setLogger( $logger );
            }


            if( count($args) && file_exists($args[0]) ) {
                $finder->paths = $args;
                foreach( $args as $file ) {
                    if ( is_file( $file ) ) {
                        require_once $file;
                    }
                }
            } 
            // load schema paths from config
            elseif( $paths = $loader->getSchemaPaths() ) {
                $finder->paths = $paths;
            }
            $finder->find();

            // load class from class map
            if( $classMap = $loader->getClassMap() ) {
                foreach( $classMap as $file => $class ) {
                    if( ! is_integer($file) && is_string($file) )
                        require $file;
                }
            }
            return $finder->getSchemas();
        }
    }

    static function breakDSN($dsn) {
        // break DSN string down into parameters
        $params = array();
        if( strpos( $dsn, ':' ) === false ) {
            $params['driver'] = $dsn;
            return $params;
        }

        list($driver,$paramString) = explode(':',$dsn,2);
        $params['driver'] = $driver;

        if( $paramString === ':memory:' ) {
            $params[':memory:'] = 1;
            return $params;
        }

        $paramPairs = explode(';',$paramString);
        foreach( $paramPairs as $pair ) {
            if( preg_match('#(\S+)=(\S+)#',$pair,$regs) ) {
                $params[$regs[1]] = $regs[2];
            }
        }
        return $params;
    }

    static function evaluate($data, $params = array() ) {
        if( $data && is_callable($data) ) {
            return call_user_func_array($data, $params );
        }
        return $data;
    }
}

